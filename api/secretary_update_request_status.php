<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/sms_helper.php';
require_once __DIR__ . '/officials_helper.php';

if (file_exists(__DIR__ . '/officials_helper.php')) require_once __DIR__ . '/officials_helper.php';
use PhpOffice\PhpWord\TemplateProcessor;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$requestId = (int)($data['request_id'] ?? 0);
$newStatus = $data['status'] ?? '';
$rejectionReason = trim($data['rejection_reason'] ?? '');

$validStatuses = ['under_review', 'approved', 'rejected', 'ready_for_pickup', 'released'];
if (!$requestId || !in_array($newStatus, $validStatuses, true)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid request or status']));
}

require_once __DIR__ . '/certificate_helper.php';

$stmt = $pdo->prepare(
    "SELECT cr.*, ct.name AS certificate_type_name, ct.template_file, r.full_name, r.address, r.civil_status, r.phone_number, r.birthdate, r.place_of_birth
     FROM certificate_requests cr
     JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
     JOIN residents r ON r.resident_id = cr.resident_id
     WHERE cr.request_id = ?"
);
$stmt->execute([$requestId]);
$request = $stmt->fetch();
if (!$request) {
    exit(json_encode(['success' => false, 'message' => 'Request not found']));
}

try {
    $pdo->beginTransaction();

    // Record which staff member handled this request the first time
    // any status action is taken on it (walk-in requests already have
    // this set at creation, so COALESCE only fills it in when blank).
    $stmt = $pdo->prepare(
        'UPDATE certificate_requests
         SET status = ?, rejection_reason = ?, handled_by = COALESCE(handled_by, ?),
             released_at = IF(? = "released", NOW(), released_at)
         WHERE request_id = ?'
    );
    $stmt->execute([
        $newStatus,
        $newStatus === 'rejected' ? $rejectionReason : null,
        $_SESSION['staff_id'],
        $newStatus,
        $requestId,
    ]);

    $generationNote = '';
    if ($newStatus === 'approved' && empty($request['pdf_path'])) {
        $templateFile = !empty($request['template_file']) ? basename($request['template_file']) : null;

        if ($templateFile) {
            $templatePath = __DIR__ . '/../frontend/templates/' . $templateFile;
            if (file_exists($templatePath)) {
                $finalData = json_decode($request['final_data'], true) ?? [];

                $processor = new TemplateProcessor($templatePath);
                $residentData = [
                    'full_name'      => $request['full_name'],
                    'address'        => $request['address'],
                    'civil_status'   => $request['civil_status'],
                    'birthdate'      => $request['birthdate'],
                    'place_of_birth' => $request['place_of_birth'] ?? '',
                ];
                populate_certificate_template($processor, $residentData, $request['certificate_type_name'], $request['cert_number'], $finalData, $pdo);

                $outputDir = __DIR__ . '/../frontend/generated_certificates';
                if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);
                $outputFilename = 'certificate_' . $requestId . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $request['cert_number']) . '.docx';
                $processor->saveAs($outputDir . '/' . $outputFilename);

                $stmt = $pdo->prepare('UPDATE certificate_requests SET pdf_path = ? WHERE request_id = ?');
                $stmt->execute(['generated_certificates/' . $outputFilename, $requestId]);
            } else {
                $generationNote = ' (Note: template file missing on server: ' . htmlspecialchars($templateFile) . ' — document was not generated.)';
            }
        } else {
            $generationNote = ' (Note: no template set up yet for "' . $request['certificate_type_name'] . '" — document was not generated.)';
        }
    }

    $messages = [
        'under_review'     => "Your {$request['certificate_type_name']} request is now under review.",
        'approved'         => "Your {$request['certificate_type_name']} request has been approved and is being prepared.",
        'rejected'         => "Your {$request['certificate_type_name']} request was rejected" . ($rejectionReason ? ": {$rejectionReason}" : '.'),
        'ready_for_pickup' => "Your {$request['certificate_type_name']} is ready for pickup at the barangay hall.",
        'released'         => "Your {$request['certificate_type_name']} has been released. Thank you!",
    ];
    $stmt = $pdo->prepare('INSERT INTO notifications (resident_id, request_id, message) VALUES (?,?,?)');
    $stmt->execute([$request['resident_id'], $requestId, $messages[$newStatus]]);

    // Send an SMS specifically when the certificate becomes ready for
    // pickup — the in-app notification stays regardless, this is an
    // additional channel for residents who aren't actively checking
    // the portal. A failed/skipped SMS never blocks the status update.
    //
    // Server-side duplicate guard: $request['status'] here is the
    // status BEFORE this update ran. If the request was already
    // 'ready_for_pickup' before this call, this is a repeat action
    // (e.g. a double-click that got through, a stale page reload, or
    // a second tab) — skip sending a second SMS for the same event.
    $smsNote = '';
    if ($newStatus === 'ready_for_pickup' && $request['status'] !== 'ready_for_pickup') {
        if (!empty($request['phone_number'])) {
            $smsMessage = "Your requested Certificate: {$request['certificate_type_name']} is ready for pick up at Barangay Hall.";
            $smsResult = send_sms($request['phone_number'], $smsMessage);
            if (!$smsResult['success']) {
                $smsNote = ' (SMS not sent: ' . $smsResult['message'] . ')';
            }
        } else {
            $smsNote = ' (SMS not sent: no phone number on file for this resident.)';
        }
    } elseif ($newStatus === 'ready_for_pickup' && $request['status'] === 'ready_for_pickup') {
        $smsNote = ' (SMS already sent previously for this status — not sent again.)';
    }

    $stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?,?,?,?)');
    $stmt->execute([$_SESSION['staff_id'], "updated request status to {$newStatus}", 'certificate_requests', $requestId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Status updated.' . $generationNote . $smsNote]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
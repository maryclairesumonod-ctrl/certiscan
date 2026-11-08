<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$residentId = (int)$_SESSION['resident_id'];

$data = json_decode(file_get_contents('php://input'), true);
$certificateTypeId = (int)($data['certificate_type_id'] ?? 0);
$extraValues = $data['extra_values'] ?? [];

if (!$certificateTypeId) {
    exit(json_encode(['success' => false, 'message' => 'Please select a certificate type']));
}

$stmt = $pdo->prepare('SELECT * FROM certificate_types WHERE certificate_type_id = ?');
$stmt->execute([$certificateTypeId]);
$certType = $stmt->fetch();

if (!$certType) {
    exit(json_encode(['success' => false, 'message' => 'Invalid certificate type selected.']));
}

if (empty($certType['is_active'])) {
    exit(json_encode(['success' => false, 'message' => 'This certificate type is currently inactive and cannot be requested.']));
}

if (empty($certType['is_available_online'])) {
    exit(json_encode(['success' => false, 'message' => 'This certificate type is not available for online requests. Please visit the barangay hall for walk-in issuance.']));
}

$stmt = $pdo->prepare('SELECT * FROM residents WHERE resident_id = ?');
$stmt->execute([$residentId]);
$resident = $stmt->fetch();

if (!$resident) {
    exit(json_encode(['success' => false, 'message' => 'Resident account not found.']));
}

// ---------- Duplicate Request Detection ----------
// Blocks a second request for the same certificate type while an
// earlier one hasn't been released yet.
$stmt = $pdo->prepare(
    "SELECT request_id FROM certificate_requests
     WHERE resident_id = ? AND certificate_type_id = ? AND status NOT IN ('released', 'rejected')
     LIMIT 1"
);
$stmt->execute([$residentId, $certificateTypeId]);
if ($stmt->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'You already have a pending request for this certificate type. Please wait for it to be processed before requesting again.']));
}

// ---------- Auto Eligibility Checker ----------
// Simple rule evaluation: min_age (from birthdate) and requires_flag
// (placeholder — flags like solo_parent would need their own column
// on residents in a fuller implementation; documented as a limitation).
$stmt = $pdo->prepare('SELECT rule_logic, rule_description FROM eligibility_rules WHERE certificate_type_id = ?');
$stmt->execute([$certificateTypeId]);
$rules = $stmt->fetchAll();

foreach ($rules as $rule) {
    $logic = json_decode($rule['rule_logic'], true);
    if (isset($logic['min_age']) && $resident['birthdate']) {
        $age = (new DateTime($resident['birthdate']))->diff(new DateTime())->y;
        if ($age < $logic['min_age']) {
            exit(json_encode(['success' => false, 'message' => 'You do not meet the eligibility requirement: ' . $rule['rule_description']]));
        }
    }
    // requires_flag checks are a placeholder for future work — see README
}

try {
    $pdo->beginTransaction();

    $finalData = [
        'full_name' => $resident['full_name'], 'address' => $resident['address'],
        'birthdate' => $resident['birthdate'], 'civil_status' => $resident['civil_status'],
        'place_of_birth' => $resident['place_of_birth'],
    ];
    $finalData = array_merge($finalData, $extraValues);

    $certNumber = sprintf('%09d-%s-%04d', random_int(1, 999999999), date('Y-m'), random_int(1, 9999));

    $stmt = $pdo->prepare(
        'INSERT INTO certificate_requests
            (resident_id, certificate_type_id, final_data, status, initiated_by, cert_number, issued_at)
         VALUES (?,?,?,"pending","resident",?,CURDATE())'
    );
    $stmt->execute([$residentId, $certificateTypeId, json_encode($finalData), $certNumber]);
    $requestId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO notifications (resident_id, request_id, message) VALUES (?,?,?)');
    $stmt->execute([$residentId, $requestId, 'Your certificate request has been submitted and is pending review.']);

    $pdo->commit();
    echo json_encode(['success' => true, 'request_id' => $requestId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

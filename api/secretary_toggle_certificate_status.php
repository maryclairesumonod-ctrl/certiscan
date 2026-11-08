<?php
// ============================================================
// Secretary API — Toggle Certificate Active / Online Status
// ============================================================

require_once __DIR__ . '/auth_guard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Authorization check: secretary or admin only
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'secretary' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized: Only the barangay secretary can manage certificate types.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$certificateTypeId = (int)($data['certificate_type_id'] ?? 0);
$field = $data['field'] ?? 'is_active'; // 'is_active' or 'is_available_online'

if (!$certificateTypeId || !in_array($field, ['is_active', 'is_available_online'], true)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid parameters.']));
}

$stmt = $pdo->prepare('SELECT certificate_type_id, name, is_active, is_available_online FROM certificate_types WHERE certificate_type_id = ?');
$stmt->execute([$certificateTypeId]);
$cert = $stmt->fetch();

if (!$cert) {
    exit(json_encode(['success' => false, 'message' => 'Certificate type not found.']));
}

$currentVal = (int)$cert[$field];
$newVal = isset($data['value']) ? ((int)$data['value'] ? 1 : 0) : ($currentVal ? 0 : 1);

try {
    $stmt = $pdo->prepare("UPDATE certificate_types SET {$field} = ? WHERE certificate_type_id = ?");
    $stmt->execute([$newVal, $certificateTypeId]);

    $fieldLabel = ($field === 'is_active') ? 'active status' : 'online availability';
    $statusText = $newVal ? 'enabled' : 'disabled';
    $auditMsg = "toggled {$fieldLabel} of '{$cert['name']}' to {$statusText}";

    $stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['staff_id'], $auditMsg, 'certificate_types', $certificateTypeId]);

    echo json_encode([
        'success'   => true,
        'message'   => "Certificate {$fieldLabel} successfully {$statusText}.",
        'field'     => $field,
        'new_value' => $newVal,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

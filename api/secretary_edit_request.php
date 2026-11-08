<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$requestId = (int)($data['request_id'] ?? 0);
$extraValues = $data['extra_values'] ?? [];

$stmt = $pdo->prepare('SELECT status, final_data FROM certificate_requests WHERE request_id = ?');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    exit(json_encode(['success' => false, 'message' => 'Request not found']));
}
if (in_array($request['status'], ['released'], true)) {
    exit(json_encode(['success' => false, 'message' => 'This request has already been released and can no longer be edited.']));
}

$finalData = json_decode($request['final_data'], true) ?? [];
$finalData = array_merge($finalData, $extraValues);

$stmt = $pdo->prepare('UPDATE certificate_requests SET final_data = ? WHERE request_id = ?');
$stmt->execute([json_encode($finalData), $requestId]);

$stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?,?,?,?)');
$stmt->execute([$_SESSION['staff_id'], 'edited request details', 'certificate_requests', $requestId]);

require_once __DIR__ . '/certificate_helper.php';
try {
    generate_or_refresh_certificate_document($requestId, $pdo, true);
} catch (\Throwable $e) {
    error_log('Error refreshing certificate on edit: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Request updated.']);

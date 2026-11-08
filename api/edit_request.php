<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$requestId = (int)($data['request_id'] ?? 0);
$extraValues = $data['extra_values'] ?? [];

// Confirm this request belongs to the logged-in resident and is still pending
$stmt = $pdo->prepare('SELECT resident_id, status, final_data FROM certificate_requests WHERE request_id = ?');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request || (int)$request['resident_id'] !== (int)$_SESSION['resident_id']) {
    exit(json_encode(['success' => false, 'message' => 'Request not found']));
}
if ($request['status'] !== 'pending') {
    exit(json_encode(['success' => false, 'message' => 'This request can no longer be edited — it is already being processed.']));
}

// Merge corrected values into the existing final_data (keeps base fields like name/address intact)
$finalData = json_decode($request['final_data'], true) ?? [];
$finalData = array_merge($finalData, $extraValues);

$stmt = $pdo->prepare('UPDATE certificate_requests SET final_data = ? WHERE request_id = ?');
$stmt->execute([json_encode($finalData), $requestId]);

require_once __DIR__ . '/certificate_helper.php';
try {
    generate_or_refresh_certificate_document($requestId, $pdo, true);
} catch (\Throwable $e) {
    error_log('Error refreshing certificate on resident edit: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Request updated successfully.']);

<?php
require_once __DIR__ . '/resident_auth_guard.php';
require_once __DIR__ . '/certificate_helper.php';
header('Content-Type: application/json');

$requestId = (int)($_GET['request_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT cr.request_id, cr.certificate_type_id, cr.final_data, cr.status,
            ct.name AS certificate_type, ct.extra_fields
     FROM certificate_requests cr
     JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
     WHERE cr.request_id = ? AND cr.resident_id = ?"
);
$stmt->execute([$requestId, $_SESSION['resident_id']]);
$request = $stmt->fetch();

if (!$request) {
    exit(json_encode(['success' => false, 'message' => 'Request not found']));
}
if ($request['status'] !== 'pending') {
    exit(json_encode(['success' => false, 'message' => 'This request can no longer be edited — it is already being processed.']));
}

$request['final_data'] = json_decode($request['final_data'], true) ?? [];
$rawExtra = json_decode($request['extra_fields'], true) ?? [];
$cleanExtra = [];
foreach ($rawExtra as $item) {
    if (!is_string($item)) continue;
    $val = trim(str_replace(['${', '}'], '', $item));
    if ($val !== '' && !is_system_placeholder($val) && !in_array($val, $cleanExtra, true)) {
        $cleanExtra[] = $val;
    }
}
$request['extra_fields'] = $cleanExtra;

echo json_encode(['success' => true, 'request' => $request]);

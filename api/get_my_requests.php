<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

$stmt = $pdo->prepare(
    "SELECT cr.request_id, cr.cert_number, cr.status, cr.rejection_reason, cr.issued_at, ct.name AS certificate_type
     FROM certificate_requests cr
     JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
     WHERE cr.resident_id = ?
     ORDER BY cr.created_at DESC"
);
$stmt->execute([$_SESSION['resident_id']]);
echo json_encode(['success' => true, 'requests' => $stmt->fetchAll()]);

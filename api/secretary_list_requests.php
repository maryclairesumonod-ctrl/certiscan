<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT cr.request_id, cr.cert_number, cr.status, cr.initiated_by, cr.rejection_reason,
               cr.created_at, cr.final_data, cr.pdf_path,
               r.full_name, r.resident_id, r.address, r.birthdate, r.civil_status, r.place_of_birth,
               ct.name AS certificate_type
        FROM certificate_requests cr
        JOIN residents r ON r.resident_id = cr.resident_id
        JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id";
$params = [];
if ($statusFilter !== '') {
    $sql .= " WHERE cr.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY cr.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

foreach ($requests as &$r) {
    $r['final_data'] = json_decode($r['final_data'], true) ?? [];
}

echo json_encode(['success' => true, 'requests' => $requests]);

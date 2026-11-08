<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

$search = trim($_GET['q'] ?? '');

$sql = "SELECT r.resident_id, r.full_name, r.address, r.registered_via,
               (SELECT COUNT(*) FROM certificate_requests WHERE resident_id = r.resident_id AND status = 'released') AS released_count,
               (SELECT COUNT(*) FROM certificate_requests WHERE resident_id = r.resident_id) AS total_count
        FROM residents r";
$params = [];
if ($search !== '') {
    $sql .= " WHERE r.full_name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY r.full_name ASC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode(['success' => true, 'residents' => $stmt->fetchAll()]);

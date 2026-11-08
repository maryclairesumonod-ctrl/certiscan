<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if ($query === '' || strlen($query) < 2) {
    echo json_encode(['success' => true, 'residents' => []]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT resident_id, full_name, address, birthdate, civil_status, place_of_birth
     FROM residents
     WHERE full_name LIKE ?
     ORDER BY full_name ASC
     LIMIT 20'
);
$stmt->execute(['%' . $query . '%']);
$residents = $stmt->fetchAll();

echo json_encode(['success' => true, 'residents' => $residents]);

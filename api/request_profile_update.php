<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$proposedChanges = [
    'address' => trim($data['address'] ?? ''),
    'civil_status' => trim($data['civil_status'] ?? ''),
];

$stmt = $pdo->prepare('INSERT INTO profile_update_requests (resident_id, proposed_changes) VALUES (?, ?)');
$stmt->execute([$_SESSION['resident_id'], json_encode($proposedChanges)]);

echo json_encode(['success' => true, 'message' => 'Your profile update request has been submitted for staff review.']);

<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$residentId = $_SESSION['resident_id'];

$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE resident_id = ? AND is_read = 0');
$stmt->execute([$residentId]);

echo json_encode([
    'success' => true,
    'message' => 'All notifications marked as read',
    'unread_count' => 0
]);

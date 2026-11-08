<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = (int)($input['notification_id'] ?? $_POST['notification_id'] ?? 0);

if (!$notificationId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Missing notification_id']));
}

$residentId = $_SESSION['resident_id'];

$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND resident_id = ?');
$stmt->execute([$notificationId, $residentId]);

$unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE resident_id = ? AND is_read = 0');
$unreadStmt->execute([$residentId]);
$unreadCount = (int)$unreadStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'message' => 'Notification marked as read',
    'notification_id' => $notificationId,
    'unread_count' => $unreadCount
]);

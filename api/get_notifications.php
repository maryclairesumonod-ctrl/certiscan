<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

$residentId = $_SESSION['resident_id'];

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE resident_id = ? ORDER BY created_at DESC LIMIT 30');
$stmt->execute([$residentId]);
$notifications = $stmt->fetchAll();

$unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE resident_id = ? AND is_read = 0');
$unreadStmt->execute([$residentId]);
$unreadCount = (int)$unreadStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);
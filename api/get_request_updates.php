<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

$residentId = $_SESSION['resident_id'];

// 1. Fetch resident requests
$reqStmt = $pdo->prepare(
    "SELECT cr.request_id, cr.cert_number, cr.status, cr.rejection_reason, cr.issued_at, cr.created_at, ct.name AS certificate_type
     FROM certificate_requests cr
     JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
     WHERE cr.resident_id = ?
     ORDER BY cr.created_at DESC"
);
$reqStmt->execute([$residentId]);
$requests = $reqStmt->fetchAll();

// 2. Fetch notifications
$notifStmt = $pdo->prepare(
    "SELECT notification_id, resident_id, request_id, message, notification_type, is_read, created_at
     FROM notifications
     WHERE resident_id = ?
     ORDER BY created_at DESC
     LIMIT 30"
);
$notifStmt->execute([$residentId]);
$notifications = $notifStmt->fetchAll();

// 3. Unread notification count
$unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE resident_id = ? AND is_read = 0');
$unreadStmt->execute([$residentId]);
$unreadCount = (int)$unreadStmt->fetchColumn();

echo json_encode([
    'success'      => true,
    'resident_id'  => $residentId,
    'requests'     => $requests,
    'notifications'=> $notifications,
    'unread_count' => $unreadCount,
    'server_time'  => date('Y-m-d H:i:s')
]);

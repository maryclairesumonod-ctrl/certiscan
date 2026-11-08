<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$role     = $_POST['role'] ?? 'secretary';

if ($fullName === '' || $username === '' || $password === '') {
    exit(json_encode(['success' => false, 'message' => 'All fields are required']));
}
if (strlen($password) < 6) {
    exit(json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']));
}
if ($password !== $confirm) {
    exit(json_encode(['success' => false, 'message' => 'Passwords do not match']));
}
if (!in_array($role, ['secretary', 'admin'], true)) {
    $role = 'secretary';
}

$stmt = $pdo->prepare('SELECT staff_id FROM staff WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'That username is already taken']));
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    'INSERT INTO staff (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$fullName, $username, $passwordHash, $role]);

echo json_encode(['success' => true, 'staff_id' => $pdo->lastInsertId()]);
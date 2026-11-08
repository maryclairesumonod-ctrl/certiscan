<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    exit(json_encode(['success' => false, 'message' => 'Username and password are required']));
}

$stmt = $pdo->prepare('SELECT staff_id, full_name, username, password_hash, role FROM staff WHERE username = ?');
$stmt->execute([$username]);
$staff = $stmt->fetch();

// NOTE: passwords should be stored with password_hash() in production.
// This checks a plain match OR a bcrypt hash so it works with the
// placeholder seed row from the database script too.
$valid = $staff && (
    password_verify($password, $staff['password_hash']) ||
    $password === $staff['password_hash']
);

if (!$valid) {
    exit(json_encode(['success' => false, 'message' => 'Invalid username or password']));
}

$_SESSION['staff_id']   = $staff['staff_id'];
$_SESSION['full_name']  = $staff['full_name'];
$_SESSION['role']       = $staff['role'];

echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);

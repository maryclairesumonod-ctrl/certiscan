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

$stmt = $pdo->prepare('SELECT resident_id, full_name, password_hash, account_status FROM residents WHERE username = ?');
$stmt->execute([$username]);
$resident = $stmt->fetch();

// Same generic message whether the username doesn't exist, the
// password is wrong, or the account isn't verified yet — this avoids
// telling an attacker which of those three is actually the case.
if (!$resident || !password_verify($password, $resident['password_hash'] ?? '') || $resident['account_status'] !== 'verified') {
    exit(json_encode(['success' => false, 'message' => 'Invalid username or password']));
}

$_SESSION['resident_id'] = $resident['resident_id'];
$_SESSION['resident_name'] = $resident['full_name'];
$_SESSION['resident_account_status'] = $resident['account_status'];

echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
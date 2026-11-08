<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);

$scanId       = !empty($data['scan_id']) ? (int)$data['scan_id'] : null;
$fullName     = trim($data['full_name'] ?? '');
$address      = trim($data['address'] ?? '');
$birthdate    = $data['birthdate'] ?? null;
$civilStatus  = $data['civil_status'] ?? null;
$placeOfBirth = trim($data['place_of_birth'] ?? '');
$username     = trim($data['username'] ?? '');
$email        = trim($data['email'] ?? '');
$phoneNumber  = trim($data['phone_number'] ?? '');
$password     = trim($data['password'] ?? '');
$idVerified   = !empty($data['id_verified']); // must be true — set only after resident_verify_id.php passed

if ($fullName === '' || $address === '' || $username === '' || $password === '' || $phoneNumber === '') {
    exit(json_encode(['success' => false, 'message' => 'Please fill in all required fields']));
}
if (!preg_match('/^09\d{9}$/', $phoneNumber)) {
    exit(json_encode(['success' => false, 'message' => 'Please enter a valid 11-digit Philippine mobile number starting with 09']));
}
if (strlen($password) < 6) {
    exit(json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']));
}
if (!$idVerified) {
    exit(json_encode(['success' => false, 'message' => 'ID verification must be completed before creating an account']));
}

$stmt = $pdo->prepare('SELECT resident_id FROM residents WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'our resident record is already registered']));
}

try {
    $pdo->beginTransaction();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Account is created as verified immediately — the ID-matching check
    // in resident_verify_id.php already served as the verification step,
    // so no separate staff approval is required.
    $stmt = $pdo->prepare(
        'INSERT INTO residents
            (full_name, address, birthdate, civil_status, place_of_birth,
             username, password_hash, email, phone_number, account_status, registered_via)
         VALUES (?,?,?,?,?,?,?,?,?,"verified","self")'
    );
    $stmt->execute([$fullName, $address, $birthdate, $civilStatus, $placeOfBirth, $username, $passwordHash, $email, $phoneNumber]);
    $residentId = $pdo->lastInsertId();

    if ($scanId) {
        $stmt = $pdo->prepare('UPDATE scans SET resident_id = ? WHERE scan_id = ?');
        $stmt->execute([$residentId, $scanId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

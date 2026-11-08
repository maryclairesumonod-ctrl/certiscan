<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);

$scanId             = !empty($data['scan_id']) ? (int)$data['scan_id'] : null;
$existingResidentId = !empty($data['resident_id']) ? (int)$data['resident_id'] : null;
$fullName           = trim($data['full_name'] ?? '');
$address            = trim($data['address'] ?? '');
$birthdate          = $data['birthdate'] ?? null;
$civilStatus        = $data['civil_status'] ?? null;
$placeOfBirth       = trim($data['place_of_birth'] ?? '');
$username           = trim($data['username'] ?? '');
$password           = $data['password'] ?? '';

if ($fullName === '' || $address === '') {
    exit(json_encode(['success' => false, 'message' => 'Full name and address are required']));
}

// Account setup is optional, but if either field was sent, both are required.
if (($username !== '' && $password === '') || ($username === '' && $password !== '')) {
    exit(json_encode(['success' => false, 'message' => 'To set up an online account, provide both username and password']));
}
if ($password !== '' && strlen($password) < 6) {
    exit(json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']));
}

try {
    $pdo->beginTransaction();

    if ($existingResidentId) {
        $residentId = $existingResidentId;

        // Don't overwrite an existing account — this resident already
        // has a username, so username/password should never have been
        // submitted for them in the first place (the form hides those
        // fields), but this guards the API itself regardless of what
        // the frontend sends.
        $stmt = $pdo->prepare('SELECT username FROM residents WHERE resident_id = ?');
        $stmt->execute([$residentId]);
        $currentUsername = $stmt->fetchColumn();

        if ($currentUsername) {
            $stmt = $pdo->prepare(
                'UPDATE residents SET full_name=?, address=?, birthdate=?, civil_status=?, place_of_birth=? WHERE resident_id=?'
            );
            $stmt->execute([$fullName, $address, $birthdate, $civilStatus, $placeOfBirth, $residentId]);
        } else {
            // No account yet — allow setting one up now, same
            // username-uniqueness check as new-resident registration.
            if ($username !== '') {
                $stmt = $pdo->prepare('SELECT resident_id FROM residents WHERE username = ? AND resident_id != ?');
                $stmt->execute([$username, $residentId]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    exit(json_encode(['success' => false, 'message' => 'That username is already taken']));
                }
                $stmt = $pdo->prepare(
                    'UPDATE residents SET full_name=?, address=?, birthdate=?, civil_status=?, place_of_birth=?, username=?, password_hash=? WHERE resident_id=?'
                );
                $stmt->execute([$fullName, $address, $birthdate, $civilStatus, $placeOfBirth, $username, password_hash($password, PASSWORD_DEFAULT), $residentId]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE residents SET full_name=?, address=?, birthdate=?, civil_status=?, place_of_birth=? WHERE resident_id=?'
                );
                $stmt->execute([$fullName, $address, $birthdate, $civilStatus, $placeOfBirth, $residentId]);
            }
        }
    } else {
        // Search for an existing resident by name + birthdate before
        // creating a new record, same matching logic as before.
        $stmt = $pdo->prepare('SELECT resident_id, username FROM residents WHERE full_name = ? AND (birthdate = ? OR birthdate IS NULL) LIMIT 1');
        $stmt->execute([$fullName, $birthdate]);
        $resident = $stmt->fetch();

        if ($resident) {
            $residentId = $resident['resident_id'];

            if ($resident['username']) {
                // Already has an account — same as above, don't touch it.
                $stmt = $pdo->prepare(
                    'UPDATE residents SET address=?, birthdate=?, civil_status=?, place_of_birth=? WHERE resident_id=?'
                );
                $stmt->execute([$address, $birthdate, $civilStatus, $placeOfBirth, $residentId]);
            } elseif ($username !== '') {
                $stmt = $pdo->prepare('SELECT resident_id FROM residents WHERE username = ? AND resident_id != ?');
                $stmt->execute([$username, $residentId]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    exit(json_encode(['success' => false, 'message' => 'That username is already taken']));
                }
                $stmt = $pdo->prepare(
                    'UPDATE residents SET address=?, birthdate=?, civil_status=?, place_of_birth=?, username=?, password_hash=? WHERE resident_id=?'
                );
                $stmt->execute([$address, $birthdate, $civilStatus, $placeOfBirth, $username, password_hash($password, PASSWORD_DEFAULT), $residentId]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE residents SET address=?, birthdate=?, civil_status=?, place_of_birth=? WHERE resident_id=?'
                );
                $stmt->execute([$address, $birthdate, $civilStatus, $placeOfBirth, $residentId]);
            }
        } else {
            // Brand new resident.
            if ($username !== '') {
                $stmt = $pdo->prepare('SELECT resident_id FROM residents WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    exit(json_encode(['success' => false, 'message' => 'That username is already taken']));
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO residents (full_name, address, birthdate, civil_status, place_of_birth, username, password_hash) VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([$fullName, $address, $birthdate, $civilStatus, $placeOfBirth, $username, password_hash($password, PASSWORD_DEFAULT)]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO residents (full_name, address, birthdate, civil_status, place_of_birth) VALUES (?,?,?,?,?)'
                );
                $stmt->execute([$fullName, $address, $birthdate, $civilStatus, $placeOfBirth]);
            }
            $residentId = $pdo->lastInsertId();
        }
    }

    if ($scanId) {
        $stmt = $pdo->prepare('UPDATE scans SET resident_id=?, status="confirmed" WHERE scan_id=?');
        $stmt->execute([$residentId, $scanId]);
    }

    $stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?,?,?,?)');
    $stmt->execute([$_SESSION['staff_id'], 'saved resident record', 'residents', $residentId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'resident_id' => $residentId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
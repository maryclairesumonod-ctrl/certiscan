<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$data      = json_decode(file_get_contents('php://input'), true);
$officials = $data['officials'] ?? null;

if (!is_array($officials) || empty($officials)) {
    exit(json_encode(['success' => false, 'message' => 'No data received']));
}

try {
    $pdo->beginTransaction();

    // Only keys that already exist in the table can be updated, so
    // a tampered request can't invent new positions.
    $existing = $pdo->query('SELECT position_key FROM barangay_officials')->fetchAll(PDO::FETCH_COLUMN);
    $update   = $pdo->prepare('UPDATE barangay_officials SET full_name = ?, updated_by = ? WHERE position_key = ?');

    foreach ($officials as $key => $name) {
        if (!in_array($key, $existing, true)) continue;

        $name = trim((string)$name);
        if ($name === '') {
            $pdo->rollBack();
            exit(json_encode(['success' => false, 'message' => 'No field can be left blank.']));
        }
        if (mb_strlen($name) > 150) {
            $pdo->rollBack();
            exit(json_encode(['success' => false, 'message' => 'A name is too long (150 characters max).']));
        }

        $update->execute([$name, $_SESSION['staff_id'], $key]);
    }

    $stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?,?,?,?)');
    $stmt->execute([$_SESSION['staff_id'], 'updated barangay officials', 'barangay_officials', 0]);

    $pdo->commit();

    // Proactively refresh existing certificates so files on disk immediately reflect the updated officials
    require_once __DIR__ . '/certificate_helper.php';
    try {
        $recentRequests = $pdo->query(
            "SELECT request_id FROM certificate_requests 
             WHERE pdf_path IS NOT NULL AND pdf_path != '' 
             ORDER BY request_id DESC LIMIT 50"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($recentRequests as $reqId) {
            generate_or_refresh_certificate_document((int)$reqId, $pdo, true);
        }
    } catch (\Throwable $e) {
        error_log('Error refreshing certificates on officials update: ' . $e->getMessage());
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
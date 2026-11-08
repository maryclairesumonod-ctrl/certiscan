<?php
require_once __DIR__ . '/resident_auth_guard.php';
header('Content-Type: application/json');

// ---------- Smart Credential Recommendation ----------
// Simple rule-based suggestions from resident profile data.
// This is intentionally lightweight (not ML-based) — documented
// as such in the thesis; a fuller version could weigh request
// history and seasonal patterns.
$stmt = $pdo->prepare('SELECT * FROM residents WHERE resident_id = ?');
$stmt->execute([$_SESSION['resident_id']]);
$resident = $stmt->fetch();

$suggestions = [];

if ($resident['civil_status'] === 'Single' && $resident['birthdate']) {
    $age = (new DateTime($resident['birthdate']))->diff(new DateTime())->y;
    if ($age >= 18 && $age <= 30) {
        $suggestions[] = 'Certificate of First Time Jobseekers Assistance';
    }
}
$suggestions[] = 'Barangay Clearance'; // commonly requested by everyone

$placeholders = implode(',', array_fill(0, count($suggestions), '?'));
$stmt = $pdo->prepare("SELECT certificate_type_id, name, description FROM certificate_types WHERE name IN ($placeholders)");
$stmt->execute($suggestions);

echo json_encode(['success' => true, 'recommendations' => $stmt->fetchAll()]);

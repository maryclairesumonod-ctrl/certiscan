<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');
// LEFT JOIN on staff — online resident requests may not have a
// handled_by assigned yet (or ever, if never touched by staff),
// so an INNER JOIN here was silently excluding them from Records.
$sql = "SELECT cr.request_id, cr.cert_number, cr.status, cr.issued_at, cr.released_at,
               cr.initiated_by, cr.pdf_path, r.full_name, ct.name AS certificate_type,
               s.full_name AS handled_by
        FROM certificate_requests cr
        JOIN residents r ON r.resident_id = cr.resident_id
        JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
        LEFT JOIN staff s ON s.staff_id = cr.handled_by
        ORDER BY cr.created_at DESC
        LIMIT 200";
$rows = $pdo->query($sql)->fetchAll();
foreach ($rows as &$row) {
    if (!$row['handled_by']) {
        $row['handled_by'] = $row['initiated_by'] === 'resident' ? 'Self-service (online)' : '—';
    }
}
echo json_encode(['success' => true, 'certificates' => $rows]);
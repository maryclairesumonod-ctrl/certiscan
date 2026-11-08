<?php
// This endpoint is read-only, non-sensitive reference data, so it
// accepts either a staff session OR a resident session — unlike most
// api/*.php files which are staff-only (auth_guard.php) or
// resident-only (resident_auth_guard.php).
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/certificate_helper.php';

if (!function_exists('clean_extra_field_list')) {
    function clean_extra_field_list($raw): array {
        $list = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
        $cleaned = [];
        foreach ($list as $item) {
            if (!is_string($item)) continue;
            $val = trim(str_replace(['${', '}'], '', $item));
            if ($val !== '' && !is_system_placeholder($val) && !in_array($val, $cleaned, true)) {
                $cleaned[] = $val;
            }
        }
        return $cleaned;
    }
}

header('Content-Type: application/json');

if (empty($_SESSION['staff_id']) && empty($_SESSION['resident_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$isStaff = !empty($_SESSION['staff_id']);
$isResident = !empty($_SESSION['resident_id']);

$isManageMode = $isStaff && (($_GET['all'] ?? '') === '1' || ($_GET['mode'] ?? '') === 'manage');

try {
    if ($isManageMode) {
        // Secretary / Admin management list: return all certificates with full management metadata
        $stmt = $pdo->query(
            "SELECT ct.certificate_type_id, ct.name, ct.body_template, ct.extra_fields, ct.description,
                    ct.template_file, ct.is_active, ct.is_available_online, ct.requirements,
                    ct.placeholder_mappings, ct.created_at, ct.updated_at,
                    (SELECT COUNT(*) FROM eligibility_rules er WHERE er.certificate_type_id = ct.certificate_type_id) AS rule_count,
                    (SELECT COUNT(*) FROM certificate_requests cr WHERE cr.certificate_type_id = ct.certificate_type_id) AS request_count
             FROM certificate_types ct
             ORDER BY ct.name"
        );
        $types = $stmt->fetchAll();

        foreach ($types as &$t) {
            $t['extra_fields'] = clean_extra_field_list($t['extra_fields']);
            $t['placeholder_mappings'] = json_decode($t['placeholder_mappings'], true) ?? [];
            $t['is_active'] = (int)$t['is_active'];
            $t['is_available_online'] = (int)$t['is_available_online'];
            $t['rule_count'] = (int)$t['rule_count'];
            $t['request_count'] = (int)$t['request_count'];
            $cleanFile = !empty($t['template_file']) ? basename($t['template_file']) : '';
            $t['template_exists'] = $cleanFile !== '' && file_exists(__DIR__ . '/../frontend/templates/' . $cleanFile);
        }
    } elseif ($isResident) {
        // Resident request list: only active and online-available certificate types
        $stmt = $pdo->query(
            "SELECT certificate_type_id, name, body_template, extra_fields, description, requirements
             FROM certificate_types
             WHERE is_active = 1 AND is_available_online = 1
             ORDER BY name"
        );
        $types = $stmt->fetchAll();

        foreach ($types as &$t) {
            $t['extra_fields'] = clean_extra_field_list($t['extra_fields']);
        }
    } else {
        // Walk-in staff issuance: all active certificate types
        $stmt = $pdo->query(
            "SELECT certificate_type_id, name, body_template, extra_fields, description, requirements, template_file
             FROM certificate_types
             WHERE is_active = 1
             ORDER BY name"
        );
        $types = $stmt->fetchAll();

        foreach ($types as &$t) {
            $t['extra_fields'] = clean_extra_field_list($t['extra_fields']);
            $cleanFile = !empty($t['template_file']) ? basename($t['template_file']) : '';
            $t['template_exists'] = $cleanFile !== '' && file_exists(__DIR__ . '/../frontend/templates/' . $cleanFile);
        }
    }

    echo json_encode(['success' => true, 'types' => $types]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
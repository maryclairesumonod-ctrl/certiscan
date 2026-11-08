<?php
// ============================================================
// Secretary API — Get Certificate Type Details
// Returns certificate configuration, eligibility rules, and
// detected template placeholders.
// ============================================================

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/certificate_helper.php';

header('Content-Type: application/json');

// Authorization check: secretary or admin only
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'secretary' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized: Secretary privileges required.']));
}

$certificateTypeId = (int)($_GET['certificate_type_id'] ?? 0);

if (!$certificateTypeId) {
    exit(json_encode(['success' => false, 'message' => 'Missing certificate_type_id.']));
}

$stmt = $pdo->prepare(
    "SELECT ct.*, 
            (SELECT COUNT(*) FROM certificate_requests cr WHERE cr.certificate_type_id = ct.certificate_type_id) AS request_count
     FROM certificate_types ct 
     WHERE ct.certificate_type_id = ?"
);
$stmt->execute([$certificateTypeId]);
$type = $stmt->fetch();

if (!$type) {
    exit(json_encode(['success' => false, 'message' => 'Certificate type not found.']));
}

$rawExtra = json_decode($type['extra_fields'], true) ?? [];
$cleanExtra = [];
foreach ($rawExtra as $item) {
    if (!is_string($item)) continue;
    $val = trim(str_replace(['${', '}'], '', $item));
    if ($val !== '' && !is_system_placeholder($val) && !in_array($val, $cleanExtra, true)) {
        $cleanExtra[] = $val;
    }
}
$type['extra_fields'] = $cleanExtra;
$type['placeholder_mappings'] = json_decode($type['placeholder_mappings'], true) ?? [];
$type['is_active'] = (int)$type['is_active'];
$type['is_available_online'] = (int)$type['is_available_online'];
$type['request_count'] = (int)$type['request_count'];

// Fetch eligibility rules
$stmt = $pdo->prepare('SELECT rule_id, rule_description, rule_logic FROM eligibility_rules WHERE certificate_type_id = ?');
$stmt->execute([$certificateTypeId]);
$rules = $stmt->fetchAll();

$minAge = null;
foreach ($rules as &$rule) {
    $rule['rule_logic'] = json_decode($rule['rule_logic'], true) ?? [];
    if (isset($rule['rule_logic']['min_age'])) {
        $minAge = (int)$rule['rule_logic']['min_age'];
    }
}
$type['rules'] = $rules;
$type['min_age'] = $minAge;

// Inspect template file if present
$cleanFile = !empty($type['template_file']) ? basename($type['template_file']) : '';
$templatePath = __DIR__ . '/../frontend/templates/' . $cleanFile;
$type['template_exists'] = ($cleanFile !== '' && file_exists($templatePath));

$detectedPlaceholders = null;
if ($type['template_exists']) {
    $detectedPlaceholders = inspect_docx_placeholders($templatePath);
}
$type['detected_placeholders'] = $detectedPlaceholders;

echo json_encode([
    'success'          => true,
    'certificate_type' => $type,
]);

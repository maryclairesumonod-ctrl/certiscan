<?php
// ============================================================
// Secretary API — Save (Create / Update) Certificate Type
// Validates input, handles secure DOCX template upload,
// extracts placeholders, and updates eligibility rules.
// ============================================================

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/certificate_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Server-side secretary/admin authorization check
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'secretary' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized: Only the barangay secretary can manage certificate types.']));
}

$certificateTypeId = (int)($_POST['certificate_type_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$isAvailableOnline = isset($_POST['is_available_online']) ? (int)$_POST['is_available_online'] : 1;
$minAge = isset($_POST['min_age']) && $_POST['min_age'] !== '' ? (int)$_POST['min_age'] : null;

// Parse extra fields (can be submitted as JSON string or comma-separated string)
$rawExtraFields = $_POST['extra_fields'] ?? '';
$extraFields = [];
if (is_string($rawExtraFields)) {
    $decoded = json_decode($rawExtraFields, true);
    if (is_array($decoded)) {
        $extraFields = $decoded;
    } else {
        $parts = array_map('trim', explode(',', $rawExtraFields));
        $extraFields = array_values(array_filter($parts));
    }
} elseif (is_array($rawExtraFields)) {
    $extraFields = $rawExtraFields;
}

// Sanitize extra field keys: lowercase, alphanumeric and underscores only
$cleanExtraFields = [];
foreach ($extraFields as $f) {
    $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($f)));
    $key = trim($key, '_');
    if ($key !== '' && !is_system_placeholder($key) && !in_array($key, $cleanExtraFields, true)) {
        $cleanExtraFields[] = $key;
    }
}

// Validation: Certificate Name is required
if ($name === '') {
    exit(json_encode(['success' => false, 'message' => 'Certificate name is required.']));
}

// Check for duplicate certificate name (case-insensitive)
if ($certificateTypeId > 0) {
    $stmt = $pdo->prepare('SELECT certificate_type_id FROM certificate_types WHERE LOWER(name) = LOWER(?) AND certificate_type_id != ?');
    $stmt->execute([$name, $certificateTypeId]);
} else {
    $stmt = $pdo->prepare('SELECT certificate_type_id FROM certificate_types WHERE LOWER(name) = LOWER(?)');
    $stmt->execute([$name]);
}
if ($stmt->fetch()) {
    exit(json_encode(['success' => false, 'message' => 'A certificate type with this name already exists. Please choose a unique name.']));
}

// Handle file upload if provided
$uploadedTemplateFile = null;
$detectedVars = null;

if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['template_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        exit(json_encode(['success' => false, 'message' => 'Template file upload failed with error code: ' . $file['error']]));
    }

    // Maximum 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        exit(json_encode(['success' => false, 'message' => 'Template file size must not exceed 10MB.']));
    }

    $origName = $file['name'];
    $extension = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if ($extension !== 'docx') {
        exit(json_encode(['success' => false, 'message' => 'Invalid file type. Only approved Microsoft Word (.docx) templates are supported.']));
    }

    // Validate DOCX structure (a real .docx is a ZIP archive containing word/document.xml)
    $zip = new ZipArchive();
    $res = $zip->open($file['tmp_name']);
    if ($res !== true) {
        exit(json_encode(['success' => false, 'message' => 'The uploaded file is not a valid Word (.docx) document archive.']));
    }

    $hasMainDoc = ($zip->locateName('word/document.xml') !== false) || ($zip->locateName('word/document22.xml') !== false);
    $zip->close();

    if (!$hasMainDoc) {
        exit(json_encode(['success' => false, 'message' => 'The uploaded file is missing standard Word document structure (word/document.xml).']));
    }

    // Sanitize filename and save to frontend/templates
    $templatesDir = __DIR__ . '/../frontend/templates';
    if (!is_dir($templatesDir)) {
        mkdir($templatesDir, 0755, true);
    }

    $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($name));
    $slug = trim($slug, '_');
    if ($slug === '') $slug = 'certificate';
    $safeFilename = 'certificate_' . $slug . '_' . time() . '.docx';
    $destination = $templatesDir . '/' . $safeFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        exit(json_encode(['success' => false, 'message' => 'Failed to save template file to the server. Check directory permissions.']));
    }

    $uploadedTemplateFile = $safeFilename;

    // Inspect placeholders in the newly uploaded template
    $detectedVars = inspect_docx_placeholders($destination);

    // If custom variables were detected and no explicit extra fields were specified, auto-populate
    if (!empty($detectedVars['custom'])) {
        foreach ($detectedVars['custom'] as $customVar) {
            if (!in_array($customVar, $cleanExtraFields, true)) {
                $cleanExtraFields[] = $customVar;
            }
        }
    }
}

try {
    $pdo->beginTransaction();

    $extraFieldsJson = json_encode($cleanExtraFields);

    if ($certificateTypeId > 0) {
        // Fetch existing record
        $stmt = $pdo->prepare('SELECT * FROM certificate_types WHERE certificate_type_id = ?');
        $stmt->execute([$certificateTypeId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $pdo->rollBack();
            exit(json_encode(['success' => false, 'message' => 'Certificate type not found.']));
        }

        $templateToSave = $uploadedTemplateFile ?? $existing['template_file'];

        // Preserve existing values if not provided in request
        $descriptionToSave = array_key_exists('description', $_POST) ? (trim($_POST['description']) ?: null) : ($existing['description'] ?? null);
        $requirementsToSave = array_key_exists('requirements', $_POST) ? (trim($_POST['requirements']) ?: null) : ($existing['requirements'] ?? null);

        $stmt = $pdo->prepare(
            'UPDATE certificate_types 
             SET name = ?, description = ?, requirements = ?, extra_fields = ?, 
                 template_file = ?, is_active = ?, is_available_online = ?
             WHERE certificate_type_id = ?'
        );
        $stmt->execute([
            $name,
            $descriptionToSave,
            $requirementsToSave,
            $extraFieldsJson,
            $templateToSave,
            $isActive,
            $isAvailableOnline,
            $certificateTypeId,
        ]);

        $auditAction = "updated certificate type: {$name}";
        $targetId = $certificateTypeId;
    } else {
        // Creating new certificate type
        $defaultBody = "THIS IS TO CERTIFY that the named resident, of legal age, Filipino, has complied with all requirements for this certification.";

        $descriptionToSave = isset($_POST['description']) ? (trim($_POST['description']) ?: null) : null;
        $requirementsToSave = isset($_POST['requirements']) ? (trim($_POST['requirements']) ?: null) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO certificate_types 
             (name, description, requirements, body_template, extra_fields, template_file, is_active, is_available_online)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $descriptionToSave,
            $requirementsToSave,
            $defaultBody,
            $extraFieldsJson,
            $uploadedTemplateFile,
            $isActive,
            $isAvailableOnline,
        ]);

        $certificateTypeId = (int)$pdo->lastInsertId();
        $auditAction = "created new certificate type: {$name}";
        $targetId = $certificateTypeId;
    }

    // Manage eligibility rules (min_age)
    if ($minAge !== null && $minAge > 0) {
        $ruleDesc = "Applicant must be at least {$minAge} years old";
        $ruleLogic = json_encode(['min_age' => $minAge]);

        // Check if an age rule already exists for this certificate type
        $stmt = $pdo->prepare(
            "SELECT rule_id FROM eligibility_rules 
             WHERE certificate_type_id = ? AND rule_logic LIKE '%min_age%'"
        );
        $stmt->execute([$certificateTypeId]);
        $existingRule = $stmt->fetch();

        if ($existingRule) {
            $stmt = $pdo->prepare('UPDATE eligibility_rules SET rule_description = ?, rule_logic = ? WHERE rule_id = ?');
            $stmt->execute([$ruleDesc, $ruleLogic, $existingRule['rule_id']]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO eligibility_rules (certificate_type_id, rule_description, rule_logic) VALUES (?, ?, ?)');
            $stmt->execute([$certificateTypeId, $ruleDesc, $ruleLogic]);
        }
    } else {
        // Remove min_age rule if cleared
        $stmt = $pdo->prepare(
            "DELETE FROM eligibility_rules 
             WHERE certificate_type_id = ? AND rule_logic LIKE '%min_age%'"
        );
        $stmt->execute([$certificateTypeId]);
    }

    // Audit log
    $stmt = $pdo->prepare('INSERT INTO audit_log (staff_id, action, target_table, target_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['staff_id'], $auditAction, 'certificate_types', $targetId]);

    $pdo->commit();

    echo json_encode([
        'success'             => true,
        'message'             => 'Certificate type saved successfully.',
        'certificate_type_id' => $certificateTypeId,
        'detected_variables'  => $detectedVars,
        'extra_fields'        => $cleanExtraFields,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

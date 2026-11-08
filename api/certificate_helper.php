<?php
// ============================================================
// Certificate Helper — Handles template inspection, placeholder
// management, and standardized template population for CertiScan.
// ============================================================

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/officials_helper.php';
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Returns the list of standard placeholders provided automatically
 * by the system (resident profile data, officials, dates, cert metadata).
 */
function get_standard_placeholder_keys(): array {
    return [
        'full_name',
        'resident_name',
        'address',
        'resident_address',
        'civil_status',
        'birthdate',
        'place_of_birth',
        'age',
        'certificate_subtitle',
        'cert_day',
        'cert_month',
        'cert_year',
        'date',
        'issued_date',
        'amount_paid',
        'cert_number',
        'certificate_number',
    ];
}

/**
 * Determines whether a placeholder variable is system-managed.
 */
function is_system_placeholder(string $key): bool {
    $standardKeys = get_standard_placeholder_keys();
    $cleanKey = strtolower(trim(str_replace(['${', '}', '$', '{'], '', $key)));
    if (in_array($cleanKey, $standardKeys, true)) {
        return true;
    }
    // Officials placeholders start with official_
    if (str_starts_with($cleanKey, 'official_')) {
        return true;
    }
    return false;
}

/**
 * Extracts and categorizes variables from a DOCX template.
 *
 * @param string $templatePath Full path to .docx file
 * @return array ['all' => [...], 'standard' => [...], 'custom' => [...]]
 */
function inspect_docx_placeholders(string $templatePath): array {
    if (!file_exists($templatePath)) {
        return ['all' => [], 'standard' => [], 'custom' => [], 'error' => 'File does not exist'];
    }

    try {
        $processor = new TemplateProcessor($templatePath);
        $variables = $processor->getVariables();
        $variables = array_values(array_unique($variables));

        $standard = [];
        $custom   = [];

        foreach ($variables as $var) {
            if (is_system_placeholder($var)) {
                $standard[] = $var;
            } else {
                $custom[] = $var;
            }
        }

        return [
            'all'      => $variables,
            'standard' => $standard,
            'custom'   => $custom,
            'error'    => null,
        ];
    } catch (\Throwable $e) {
        return [
            'all'      => [],
            'standard' => [],
            'custom'   => [],
            'error'    => $e->getMessage(),
        ];
    }
}

/**
 * Fills standard placeholders, officials, and extra custom fields into
 * a TemplateProcessor instance.
 */
function populate_certificate_template(
    TemplateProcessor $processor,
    array $resident,
    string $certificateName,
    string $certNumber,
    array $extraValues,
    PDO $pdo
): void {
    $today = new DateTime();

    // 1. Apply barangay officials
    apply_officials_if_available($processor, $pdo);

    // 2. Standard resident information and template aliases
    $processor->setValue('full_name', htmlspecialchars($resident['full_name'] ?? ''));
    $processor->setValue('resident_name', htmlspecialchars($resident['full_name'] ?? ''));
    $processor->setValue('address', htmlspecialchars($resident['address'] ?? ''));
    $processor->setValue('resident_address', htmlspecialchars($resident['address'] ?? ''));
    $processor->setValue('civil_status', htmlspecialchars($resident['civil_status'] ?? ''));
    $processor->setValue('place_of_birth', htmlspecialchars($resident['place_of_birth'] ?? ''));
    $processor->setValue('birthdate', htmlspecialchars($resident['birthdate'] ?? ''));

    $age = '';
    if (!empty($resident['birthdate'])) {
        try {
            $age = (new DateTime($resident['birthdate']))->diff($today)->y;
        } catch (\Exception $e) {
            $age = '';
        }
    }
    $processor->setValue('age', $age);

    // 3. Document metadata
    $processor->setValue('certificate_subtitle', '(' . $certificateName . ')');
    $processor->setValue('cert_day', $today->format('jS'));
    $processor->setValue('cert_month', $today->format('F'));
    $processor->setValue('cert_year', $today->format('Y'));
    $processor->setValue('issued_date', $today->format('m/d/Y'));
    $processor->setValue('date', $today->format('m/d/Y'));
    $processor->setValue('amount_paid', $extraValues['amount_paid'] ?? '0.00');
    $processor->setValue('cert_number', $certNumber);
    $processor->setValue('certificate_number', $certNumber);

    // 4. Custom certificate-specific extra fields
    foreach ($extraValues as $key => $value) {
        if (!is_array($value)) {
            $cleanKey = strtolower(trim(str_replace(['${', '}', '$', '{'], '', $key)));
            $processor->setValue($cleanKey, htmlspecialchars((string)$value));
            $processor->setValue($key, htmlspecialchars((string)$value));
        }
    }
}


/**
 * Dynamically generates or refreshes the certificate .docx file for a request.
 * Automatically synchronizes with the latest Barangay Officials records, resident
 * profile details, and certificate-specific extra fields from the database.
 * If cached documents or PDF files are older than the latest saved official settings,
 * they are automatically invalidated and regenerated on the fly.
 */
function generate_or_refresh_certificate_document(int $requestId, PDO $pdo, bool $force = false): ?string {
    $stmt = $pdo->prepare(
        "SELECT cr.*, ct.name AS certificate_type_name, ct.template_file,
                r.full_name, r.address, r.civil_status, r.phone_number, r.birthdate, r.place_of_birth
         FROM certificate_requests cr
         JOIN certificate_types ct ON ct.certificate_type_id = cr.certificate_type_id
         JOIN residents r ON r.resident_id = cr.resident_id
         WHERE cr.request_id = ?"
    );
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        return null;
    }

    $cleanTemplate = !empty($request['template_file']) ? basename($request['template_file']) : '';
    $templatePath = __DIR__ . '/../frontend/templates/' . $cleanTemplate;
    if ($cleanTemplate === '' || !file_exists($templatePath)) {
        return null;
    }

    $outputDir = __DIR__ . '/../frontend/generated_certificates';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $cleanCertNum = preg_replace('/[^a-zA-Z0-9]/', '', $request['cert_number'] ?: ('req_' . $requestId));
    $expectedFilename = 'certificate_' . $requestId . '_' . $cleanCertNum . '.docx';

    $currentDocxName = !empty($request['pdf_path']) ? basename($request['pdf_path']) : $expectedFilename;
    if (!str_ends_with(strtolower($currentDocxName), '.docx')) {
        $currentDocxName = preg_replace('/\.[^.]+$/', '', $currentDocxName) . '.docx';
    }
    $docxPath = $outputDir . '/' . $currentDocxName;
    $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);

    $docxExists = file_exists($docxPath);
    $docxMtime = $docxExists ? filemtime($docxPath) : 0;

    // Check latest update time of barangay_officials
    $latestOfficialTime = $pdo->query('SELECT MAX(updated_at) FROM barangay_officials')->fetchColumn();
    $officialsMtime = $latestOfficialTime ? strtotime($latestOfficialTime) : 0;

    // Check request update time
    $requestMtime = !empty($request['updated_at']) ? strtotime($request['updated_at']) : 0;

    $needsRefresh = $force 
        || !$docxExists 
        || ($officialsMtime > $docxMtime)
        || ($requestMtime > $docxMtime);

    if ($needsRefresh) {
        $processor = new TemplateProcessor($templatePath);
        $residentData = [
            'full_name'      => $request['full_name'],
            'address'        => $request['address'],
            'civil_status'   => $request['civil_status'],
            'birthdate'      => $request['birthdate'],
            'place_of_birth' => $request['place_of_birth'] ?? '',
        ];
        $finalData = json_decode($request['final_data'], true) ?? [];

        populate_certificate_template(
            $processor,
            $residentData,
            $request['certificate_type_name'],
            $request['cert_number'] ?: ('CERT-' . $requestId),
            $finalData,
            $pdo
        );
        $processor->saveAs($docxPath);

        // Remove old converted PDF so it re-converts from the freshly generated docx
        if (file_exists($pdfPath)) {
            @unlink($pdfPath);
        }

        $relPath = 'generated_certificates/' . $currentDocxName;
        if ($request['pdf_path'] !== $relPath) {
            $updateStmt = $pdo->prepare('UPDATE certificate_requests SET pdf_path = ? WHERE request_id = ?');
            $updateStmt->execute([$relPath, $requestId]);
        }
    }

    return $docxPath;
}
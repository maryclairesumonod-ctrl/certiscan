<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/secrets.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (empty($_FILES['id_image']) || $_FILES['id_image']['error'] !== UPLOAD_ERR_OK) {
    exit(json_encode(['success' => false, 'message' => 'No image uploaded or upload error']));
}

$file        = $_FILES['id_image'];
$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
$mimeType    = mime_content_type($file['tmp_name']);

if (!in_array($mimeType, $allowedMime, true)) {
    exit(json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed']));
}

if ($file['size'] > 1 * 1024 * 1024) {
    exit(json_encode(['success' => false, 'message' => 'Image is too large — please keep it under 1MB']));
}

$imageData    = file_get_contents($file['tmp_name']);
$documentType = $_POST['document_type'] ?? 'generic_id';

/** Step 1: OCR.space — turns the image into raw text */
function run_ocr_space(string $imagePath): array {
    $mime = mime_content_type($imagePath);
    $extension = match ($mime) {
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $uploadFilename = 'scan.' . $extension;

    $ch = curl_init('https://api.ocr.space/parse/image');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => [
            'apikey'    => OCR_SPACE_API_KEY,
            'file'      => new CURLFile($imagePath, $mime, $uploadFilename),
            'language'  => 'eng',
            'OCREngine' => '2',
            'scale'     => 'true',
            'isTable'   => 'false',
        ],
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['text' => '', 'debug' => 'OCR_CURL_ERROR: ' . $curlError];
    }

    $data = json_decode($response, true);

    if (!empty($data['IsErroredOnProcessing'])) {
        $errMsg = $data['ErrorMessage'][0] ?? 'Unknown OCR.space error';
        return ['text' => '', 'debug' => 'OCR_ERROR: ' . $errMsg];
    }

    if (empty($data) || !isset($data['ParsedResults'])) {
        return ['text' => '', 'debug' => 'OCR_UNEXPECTED_RESPONSE: ' . substr($response, 0, 300)];
    }

    $text = $data['ParsedResults'][0]['ParsedText'] ?? '';
    return ['text' => $text, 'debug' => $text === '' ? 'OCR_NO_TEXT_FOUND' : 'OK'];
}

/**
 * Step 2: Gemini — reads the messy OCR text and understands it,
 * instead of us guessing with regex. Returns clean structured JSON.
 */
function extract_fields_with_gemini(string $rawText, string $documentType): array {
    $emptyFields = [
        'name' => '', 'address' => '', 'birthdate' => null,
        'civil_status' => '', 'place_of_birth' => '',
    ];

    if (trim($rawText) === '') {
        return array_merge($emptyFields, ['debug' => 'GEMINI_SKIPPED_NO_OCR_TEXT']);
    }

    $documentHint = $documentType === 'psa_birth_certificate'
        ? 'This text is from a Philippine PSA Certificate of Live Birth. Extract the CHILD\'s information (not the mother\'s or father\'s), except for address which should be the family\'s residence.'
        : 'This text is from a Philippine government-issued ID or document.';

    $prompt = <<<PROMPT
{$documentHint}

The text below was extracted by OCR and may contain misread characters, jumbled line order, or stray form-label text (e.g. "(First)", "(Middle)", "City/Municipality"). Read it the way a human would, understanding context, and extract ONLY these five fields:

- name: the person's full name (first, middle, last combined naturally)
- address: their residence/address
- birthdate: in YYYY-MM-DD format, or null if not found
- civil_status: one of Single, Married, Widowed, Separated, Divorced, or empty string if not found
- place_of_birth: city/municipality and province of birth, or empty string if not found

Ignore form labels, field numbers, watermark text, and registrar/signature names — those are not the fields being requested.

Reply with ONLY valid JSON in this exact shape, no markdown, no explanation:
{"name": "", "address": "", "birthdate": null, "civil_status": "", "place_of_birth": ""}

OCR TEXT:
{$rawText}
PROMPT;

    $payload = json_encode([
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            'temperature' => 0,
            'responseMimeType' => 'application/json',
        ],
    ]);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return array_merge($emptyFields, ['debug' => 'GEMINI_CURL_ERROR: ' . $curlError]);
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $apiError = $data['error']['message'] ?? substr($response, 0, 300);
        return array_merge($emptyFields, ['debug' => "GEMINI_HTTP_{$httpCode}: {$apiError}"]);
    }

    $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $parsed   = json_decode($jsonText, true);

    if (!is_array($parsed)) {
        return array_merge($emptyFields, ['debug' => 'GEMINI_BAD_JSON: ' . substr($jsonText, 0, 300)]);
    }

    return [
        'name'           => $parsed['name'] ?? '',
        'address'        => $parsed['address'] ?? '',
        'birthdate'      => $parsed['birthdate'] ?? null,
        'civil_status'   => $parsed['civil_status'] ?? '',
        'place_of_birth' => $parsed['place_of_birth'] ?? '',
        'debug'          => 'OK',
    ];
}

// --- Run the pipeline ---
$ocrResult = run_ocr_space($file['tmp_name']);
$rawText   = $ocrResult['text'];

$geminiResult = extract_fields_with_gemini($rawText, $documentType);

$ocr = [
    'raw_text'       => $rawText,
    'confidence'     => null,
    'debug'          => "OCR: {$ocrResult['debug']} | GEMINI: {$geminiResult['debug']}",
    'name'           => mb_substr($geminiResult['name'] ?? '', 0, 145),
    'address'        => mb_substr($geminiResult['address'] ?? '', 0, 250),
    'birthdate'      => $geminiResult['birthdate'] ?? null,
    'civil_status'   => mb_substr($geminiResult['civil_status'] ?? '', 0, 45),
    'place_of_birth' => mb_substr($geminiResult['place_of_birth'] ?? '', 0, 145),
];

/**
 * Same normalization used in resident_verify_id.php, kept consistent
 * across both the online registration check and this walk-in check.
 */
function normalize_for_barangay_check(string $text): string {
    $text = strtoupper($text);
    $text = preg_replace('/[^A-Z0-9\s]/', '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// Residency restriction: this system only processes residents of
// Barangay Balongating. If the scanned ID's extracted address doesn't
// mention Balongating at all, reject the scan outright — same rule
// already enforced on the online registration side.
$isBalongatingResident = str_contains(normalize_for_barangay_check($ocr['address']), 'BALONGATING');

$stmt = $pdo->prepare(
    'INSERT INTO scans
        (resident_id, id_image, image_mime_type, raw_ocr_text,
         extracted_name, extracted_address, extracted_birthdate,
         extracted_civil_status, extracted_place_of_birth,
         confidence_score, status, scanned_by)
     VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending_review", ?)'
);

$stmt->execute([
    $imageData, $mimeType, $ocr['raw_text'], $ocr['name'], $ocr['address'],
    $ocr['birthdate'], $ocr['civil_status'], $ocr['place_of_birth'],
    $ocr['confidence'], $_SESSION['staff_id'],
]);

if (!$isBalongatingResident) {
    exit(json_encode([
        'success'  => false,
        'scan_id'  => $pdo->lastInsertId(),
        'message'  => 'This ID does not show a Barangay Balongating address ("' . ($ocr['address'] ?: 'no address found') . '"). This system only processes requests for residents of Barangay Balongating.',
        'debug'    => $ocr['debug'],
        'raw_text' => $ocr['raw_text'],
    ]));
}

echo json_encode(['success' => true, 'scan_id' => $pdo->lastInsertId(), 'extracted' => $ocr]);

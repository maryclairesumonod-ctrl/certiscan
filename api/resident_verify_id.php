<?php
// ============================================================
// Verifies manually-entered registration data against an
// uploaded ID by running OCR + Gemini extraction and comparing
// fields, instead of using OCR to auto-fill the form.
//
// STRICT MODE: name and address now require a 100% word match
// against the ID (every word typed must be found on the ID).
// Birthdate remains an exact string match, same as before.
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/secrets.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (empty($_FILES['id_image']) || $_FILES['id_image']['error'] !== UPLOAD_ERR_OK) {
    exit(json_encode(['success' => false, 'message' => 'No image uploaded or upload error']));
}

$file = $_FILES['id_image'];
$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
$mimeType = mime_content_type($file['tmp_name']);
if (!in_array($mimeType, $allowedMime, true)) {
    exit(json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed']));
}
if ($file['size'] > 1 * 1024 * 1024) {
    exit(json_encode(['success' => false, 'message' => 'Image is too large — please keep it under 1MB']));
}

$enteredFullName    = trim($_POST['full_name'] ?? '');
$enteredAddress     = trim($_POST['address'] ?? '');
$enteredBirthdate   = trim($_POST['birthdate'] ?? '');
$enteredCivilStatus = trim($_POST['civil_status'] ?? '');

$imageData = file_get_contents($file['tmp_name']);

function run_ocr_space(string $imagePath): array {
    $mime = mime_content_type($imagePath);
    $extension = match ($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
    $ch = curl_init('https://api.ocr.space/parse/image');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => [
            'apikey' => OCR_SPACE_API_KEY,
            'file' => new CURLFile($imagePath, $mime, 'scan.' . $extension),
            'language' => 'eng', 'OCREngine' => '2', 'scale' => 'true',
        ],
    ]);
    $response = curl_exec($ch);
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

function extract_fields_with_gemini(string $rawText): array {
    $empty = ['name' => '', 'address' => '', 'birthdate' => null, 'civil_status' => ''];
    if (trim($rawText) === '') return array_merge($empty, ['debug' => 'GEMINI_SKIPPED_NO_OCR_TEXT']);

    $prompt = "This text is from a Philippine government-issued ID. Extract ONLY: name, address, birthdate (YYYY-MM-DD), civil_status. Reply with ONLY valid JSON: {\"name\":\"\",\"address\":\"\",\"birthdate\":null,\"civil_status\":\"\"}\n\nOCR TEXT:\n{$rawText}";

    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json'],
    ]);
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=' . GEMINI_API_KEY);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return array_merge($empty, ['debug' => 'GEMINI_CURL_ERROR: ' . $curlError]);
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $apiError = $data['error']['message'] ?? substr($response, 0, 300);
        return array_merge($empty, ['debug' => "GEMINI_HTTP_{$httpCode}: {$apiError}"]);
    }

    $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $parsed = json_decode($jsonText, true);

    if (!is_array($parsed)) {
        return array_merge($empty, ['debug' => 'GEMINI_BAD_JSON: ' . substr($jsonText, 0, 300)]);
    }

    return array_merge($empty, $parsed, ['debug' => 'OK']);
}

/** Normalizes text for comparison: uppercase, strip extra spaces/punctuation */
function normalize(string $text): string {
    $text = strtoupper($text);
    $text = preg_replace('/[^A-Z0-9\s]/', '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * Word-by-word breakdown of a comparison between what was typed and
 * what OCR found — used for both name and address now. Returns each
 * word with whether it was found on the ID, plus the overall ratio.
 */
function word_by_word_comparison(string $entered, string $extracted): array {
    $enteredWords = array_values(array_filter(explode(' ', normalize($entered))));
    $extractedNorm = normalize($extracted);
    $breakdown = [];
    $matchedCount = 0;

    foreach ($enteredWords as $word) {
        if (strlen($word) < 2) continue; // skip single-letter tokens (e.g. middle initials)
        $isMatch = str_contains($extractedNorm, $word);
        if ($isMatch) $matchedCount++;
        $breakdown[] = ['word' => $word, 'matched' => $isMatch];
    }

    $total = count($breakdown);
    $ratio = $total > 0 ? $matchedCount / $total : 0;

    return ['breakdown' => $breakdown, 'ratio' => $ratio];
}

$ocrResult = run_ocr_space($file['tmp_name']);
$rawText = $ocrResult['text'];
$extracted = extract_fields_with_gemini($rawText);
$combinedDebug = "OCR: {$ocrResult['debug']} | GEMINI: {$extracted['debug']}";

$mismatches = [];

// ---------- Name: STRICT — every word must match (100%) ----------
$nameComparison = word_by_word_comparison($enteredFullName, $extracted['name']);
if ($extracted['name'] === '' || $nameComparison['ratio'] < 1.0) {
    $mismatches[] = [
        'field' => 'name',
        'entered' => $enteredFullName,
        'found' => $extracted['name'] !== '' ? $extracted['name'] : '(no name could be read from the ID)',
        'wordComparison' => $nameComparison['breakdown'],
    ];
}

// ---------- Address: STRICT — every word must match (100%) ----------
$addressComparison = word_by_word_comparison($enteredAddress, $extracted['address']);
if ($extracted['address'] === '' || $addressComparison['ratio'] < 1.0) {
    $mismatches[] = [
        'field' => 'address',
        'entered' => $enteredAddress,
        'found' => $extracted['address'] !== '' ? $extracted['address'] : '(no address could be read from the ID)',
        'wordComparison' => $addressComparison['breakdown'],
    ];
}

// ---------- Residency restriction: registration is limited to ----------
// ---------- residents whose ID shows a Barangay Balongating address ----------
// Checked independently of the word-match above — even a perfect
// word match against the ID doesn't matter if that address simply
// isn't within Barangay Balongating.
if (empty($mismatches) || (count($mismatches) === 1 && $mismatches[0]['field'] !== 'address')) {
    // Only run this check if the address itself wasn't already flagged,
    // to avoid a confusing double-error on the same field.
    $addressIsBalongating = str_contains(normalize($extracted['address']), 'BALONGATING')
        || str_contains(normalize($enteredAddress), 'BALONGATING');

    if (!$addressIsBalongating) {
        $mismatches[] = [
            'field' => 'address',
            'entered' => $enteredAddress,
            'found' => $extracted['address'],
            'wordComparison' => [],
            'residencyRestricted' => true,
        ];
    }
}

// ---------- Birthdate: STRICT — exact match, only checked if OCR found one ----------
$birthdateComparison = [];
if (!empty($extracted['birthdate']) && $enteredBirthdate !== '') {
    $birthdateMatches = ($extracted['birthdate'] === $enteredBirthdate);
    $birthdateComparison = [['word' => $enteredBirthdate, 'matched' => $birthdateMatches]];
    if (!$birthdateMatches) {
        $mismatches[] = ['field' => 'birthdate', 'entered' => $enteredBirthdate, 'found' => $extracted['birthdate']];
    }
}

// ---------- Civil status: only checked if OCR actually found one on the ID (rare) ----------
if (!empty($extracted['civil_status']) && $enteredCivilStatus !== '') {
    if (strtoupper($extracted['civil_status']) !== strtoupper($enteredCivilStatus)) {
        $mismatches[] = ['field' => 'civil_status', 'entered' => $enteredCivilStatus, 'found' => $extracted['civil_status']];
    }
}

$fieldLabels = [
    'name' => 'Full name', 'birthdate' => 'Birthdate',
    'address' => 'Address', 'civil_status' => 'Civil status',
];
foreach ($mismatches as &$m) {
    $m['label'] = $fieldLabels[$m['field']];
}
unset($m);

$stmt = $pdo->prepare(
    'INSERT INTO scans (resident_id, id_image, image_mime_type, raw_ocr_text,
        extracted_name, extracted_address, extracted_birthdate, extracted_civil_status, status, scanned_by)
     VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, NULL)'
);
$stmt->execute([
    $imageData, $mimeType, $rawText, $extracted['name'], $extracted['address'],
    $extracted['birthdate'], $extracted['civil_status'],
    empty($mismatches) ? 'confirmed' : 'pending_review',
]);
$scanId = $pdo->lastInsertId();

$isResidencyBlocked = false;
foreach ($mismatches as $m) {
    if (!empty($m['residencyRestricted'])) { $isResidencyBlocked = true; break; }
}

echo json_encode([
    'success'             => empty($mismatches),
    'scan_id'             => $scanId,
    'mismatches'          => $mismatches,
    'nameComparison'      => $nameComparison['breakdown'],
    'addressComparison'   => $addressComparison['breakdown'],
    'birthdateComparison' => $birthdateComparison,
    'debug'               => $combinedDebug,
    'raw_text'            => $rawText,
    'message'             => empty($mismatches)
        ? 'ID verified successfully.'
        : ($isResidencyBlocked
            ? 'Registration is limited to residents of Barangay Balongating. Your ID does not show a Barangay Balongating address.'
            : 'Some details did not fully match your ID. Every word must match exactly for verification to pass.'),
]);

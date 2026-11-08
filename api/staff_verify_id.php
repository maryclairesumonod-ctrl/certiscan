<?php
// ============================================================
// Verifies staff registration data against an uploaded ID.
// Same OCR + Gemini approach as resident_verify_id.php, but
// with STRICTER matching thresholds, since a staff account
// carries elevated access (approving/rejecting requests,
// generating official credentials) compared to a resident one.
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

$enteredFullName  = trim($_POST['full_name'] ?? '');
$enteredAddress   = trim($_POST['address'] ?? '');
$enteredBirthdate = trim($_POST['birthdate'] ?? '');

$imageData = file_get_contents($file['tmp_name']);

function run_ocr_space(string $imagePath): string {
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
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['ParsedResults'][0]['ParsedText'] ?? '';
}

function extract_fields_with_gemini(string $rawText): array {
    $empty = ['name' => '', 'address' => '', 'birthdate' => null];
    if (trim($rawText) === '') return $empty;

    $prompt = "This text is from a Philippine government-issued ID. Extract ONLY: name, address, birthdate (YYYY-MM-DD). Reply with ONLY valid JSON: {\"name\":\"\",\"address\":\"\",\"birthdate\":null}\n\nOCR TEXT:\n{$rawText}";

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
    curl_close($ch);
    $data = json_decode($response, true);
    $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $parsed = json_decode($jsonText, true);
    return is_array($parsed) ? array_merge($empty, $parsed) : $empty;
}

function normalize(string $text): string {
    $text = strtoupper($text);
    $text = preg_replace('/[^A-Z0-9\s]/', '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function word_overlap_ratio(string $entered, string $extracted): float {
    $enteredWords = array_filter(explode(' ', normalize($entered)));
    if (empty($enteredWords)) return 0;
    $extractedNorm = normalize($extracted);
    $matched = 0;
    foreach ($enteredWords as $word) {
        if (strlen($word) < 2) continue;
        if (str_contains($extractedNorm, $word)) $matched++;
    }
    return $matched / count($enteredWords);
}

function word_by_word_comparison(string $entered, string $extracted): array {
    $enteredWords = array_values(array_filter(explode(' ', normalize($entered))));
    $extractedNorm = normalize($extracted);
    $breakdown = [];
    $matchedCount = 0;
    foreach ($enteredWords as $word) {
        if (strlen($word) < 2) continue;
        $isMatch = str_contains($extractedNorm, $word);
        if ($isMatch) $matchedCount++;
        $breakdown[] = ['word' => $word, 'matched' => $isMatch];
    }
    $total = count($breakdown);
    $ratio = $total > 0 ? $matchedCount / $total : 0;
    return ['breakdown' => $breakdown, 'ratio' => $ratio];
}

$rawText = run_ocr_space($file['tmp_name']);
$extracted = extract_fields_with_gemini($rawText);

$mismatches = [];

// ---------- STRICT thresholds ----------
// Name: require 80% of entered words to appear on the ID (resident
// version only requires 50%) — leaves very little room for a
// mismatched identity to slip through on a staff account.
$nameComparison = word_by_word_comparison($enteredFullName, $extracted['name']);
if ($extracted['name'] === '' || $nameComparison['ratio'] < 0.8) {
    $mismatches[] = [
        'field' => 'name', 'entered' => $enteredFullName,
        'found' => $extracted['name'] !== '' ? $extracted['name'] : '(no name could be read from the ID)',
        'wordComparison' => $nameComparison['breakdown'],
    ];
}

// Birthdate: required to match exactly if entered. Unlike the
// resident flow, a staff registration is rejected outright if no
// birthdate could be read from the ID at all, rather than skipping
// the check silently.
if (empty($extracted['birthdate'])) {
    $mismatches[] = ['field' => 'birthdate', 'entered' => $enteredBirthdate, 'found' => '(no birthdate could be read from the ID)'];
} elseif ($extracted['birthdate'] !== $enteredBirthdate) {
    $mismatches[] = ['field' => 'birthdate', 'entered' => $enteredBirthdate, 'found' => $extracted['birthdate']];
}

// Address: require 60% overlap (resident version requires 30%)
if (empty($extracted['address'])) {
    $mismatches[] = ['field' => 'address', 'entered' => $enteredAddress, 'found' => '(no address could be read from the ID)'];
} else {
    $ratio = word_overlap_ratio($enteredAddress, $extracted['address']);
    if ($ratio < 0.6) {
        $mismatches[] = ['field' => 'address', 'entered' => $enteredAddress, 'found' => $extracted['address']];
    }
}

$fieldLabels = ['name' => 'Full name', 'birthdate' => 'Birthdate', 'address' => 'Address'];
foreach ($mismatches as &$m) {
    $m['label'] = $fieldLabels[$m['field']];
}
unset($m);

$stmt = $pdo->prepare(
    'INSERT INTO scans (resident_id, id_image, image_mime_type, raw_ocr_text,
        extracted_name, extracted_address, extracted_birthdate, status, scanned_by)
     VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, NULL)'
);
$stmt->execute([
    $imageData, $mimeType, $rawText, $extracted['name'], $extracted['address'],
    $extracted['birthdate'], empty($mismatches) ? 'confirmed' : 'pending_review',
]);
$scanId = $pdo->lastInsertId();

echo json_encode([
    'success'        => empty($mismatches),
    'scan_id'        => $scanId,
    'mismatches'     => $mismatches,
    'nameComparison' => $nameComparison['breakdown'],
    'message'        => empty($mismatches)
        ? 'ID verified successfully.'
        : 'Some details did not match your ID. Staff accounts require a strict match — please double-check your entries or upload a clearer photo.',
]);

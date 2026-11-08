<?php
// ============================================================
// SMS Gateway API (simpapp, sms-gateway-api-simpapp.web.app)
// integration — plain cURL against their documented V2 REST
// endpoint (Bearer token auth, their recommended method for new
// integrations over the legacy X-API-Key V1).
//
// IMPORTANT — same operational model as before: this service
// relays requests to a real Android phone running their app
// (with a SIM card, powered on, connected to the internet). The
// API itself does not send SMS directly — if that phone is off
// or offline, sends will fail with a 503.
//
// Endpoint: POST https://europe-west1-sms-gateway-api-simpapp
//                 .cloudfunctions.net/api_v2_sms_send
// Auth: Authorization: Bearer YOUR_API_KEY
// ============================================================

/**
 * Sends an SMS via SMS Gateway API (simpapp). Returns an array
 * with 'success' and either 'message_id' (on success) or
 * 'message' (on failure). Never throws — failures are meant to
 * be logged/ignored so a notification hiccup never blocks the
 * certificate workflow.
 */
function send_sms(string $recipientPhone, string $content): array {
    if (!defined('SMS_GATEWAY_API_KEY') || SMS_GATEWAY_API_KEY === 'PASTE_YOUR_SMS_GATEWAY_API_KEY_HERE') {
        return ['success' => false, 'message' => 'SMS Gateway API is not configured yet.'];
    }

    $recipient = normalize_ph_phone_number($recipientPhone);
    if (!$recipient) {
        return ['success' => false, 'message' => 'Invalid or missing phone number.'];
    }

    $payload = json_encode([
        'phoneNumber' => $recipient,
        'message'     => mb_substr($content, 0, 160), // keep to a single SMS segment
    ]);

    $ch = curl_init('https://europe-west1-sms-gateway-api-simpapp.cloudfunctions.net/api_v2_sms_send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SMS_GATEWAY_API_KEY,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT    => 15,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'message' => 'SMS_CURL_ERROR: ' . $curlError];
    }

    $data = json_decode($response, true);

    // The API's real responses don't include a "success" boolean —
    // a successful send returns HTTP 200 with a "status" field like
    // "queued". Only HTTP code (and any explicit "error" field) are
    // reliable signals of an actual failure.
    if ($httpCode !== 200 || !empty($data['error'])) {
        // Documented error codes: 400 missing params, 401 bad key,
        // 403 subscription required, 429 rate limit, 503 device offline.
        $errorMeanings = [
            400 => 'Missing phoneNumber or message.',
            401 => 'Invalid or missing API key.',
            403 => 'Subscription required — upgrade to PRO in the app.',
            429 => 'Rate limit exceeded — slow down or upgrade to PRO.',
            503 => 'Device offline — ensure the Android app is running and connected.',
        ];
        $apiError = $data['error'] ?? ($errorMeanings[$httpCode] ?? substr($response, 0, 300));
        return ['success' => false, 'message' => "SMS_HTTP_{$httpCode}: {$apiError}"];
    }

    return [
        'success'    => true,
        'message_id' => $data['smsId'] ?? null,
        'status'     => $data['status'] ?? null, // typically "queued"
    ];
}

/**
 * Converts a locally-typed PH mobile number (e.g. "09171234567" or
 * "9171234567") into the E.164 format this API expects — WITH the
 * leading plus sign (+639171234567), unlike some other providers
 * we've tried that wanted it without the plus.
 */
function normalize_ph_phone_number(?string $phone): ?string {
    if (empty($phone)) return null;

    $digits = preg_replace('/\D/', '', $phone); // strip everything except digits first

    if (str_starts_with($digits, '63') && strlen($digits) === 12) {
        return '+' . $digits;
    }
    if (str_starts_with($digits, '0') && strlen($digits) === 11) {
        return '+63' . substr($digits, 1);
    }
    if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
        return '+63' . $digits;
    }

    return null;
}
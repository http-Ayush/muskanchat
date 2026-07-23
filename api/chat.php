<?php
// Disable error reporting to prevent warnings/notices from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed. Use POST."]);
    exit();
}

// Get raw POST input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['message']) || empty(trim($data['message']))) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'message' is required."]);
    exit();
}

$userMessage = $data['message'];

// API Key setup
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    // Fallback key provided by the user
    $apiKey = "AQ.Ab8RN6Jywbhm041ZPlboAxZRTzHLdZL7qlFBTJPQ1A4r9dYYKA";
}

$model = "gemini-3.5-flash";
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

$systemPrompt = "You are Muskan, a friendly virtual girl character.\n\nRules:\n- Reply in the same language as the user's message.\n- Support all languages.\n- Keep replies short (around 10–20 characters whenever possible).\n- Always be kind and friendly.\n- Use emojis.\n- Sometimes use a cute compliment.\n- Sometimes use a light playful flirting line.\n- Never use explicit sexual content.";

// Prepare payload for Gemini API
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $userMessage]
            ]
        ]
    ],
    "systemInstruction" => [
        "parts" => [
            ["text" => $systemPrompt]
        ]
    ],
    "generationConfig" => [
        "maxOutputTokens" => 60,
        "temperature" => 0.7
    ]
];

// Execute cURL request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    $errorDetails = json_decode($response, true);
    
    if ($httpCode === 401) {
        echo json_encode([
            "error" => "Muskan: Oh no! It seems your Gemini API key is missing or invalid on Vercel. Please add a valid GEMINI_API_KEY to your Vercel Project Environment Variables and redeploy.",
            "details" => $errorDetails
        ]);
    } else {
        echo json_encode([
            "error" => "Gemini API error",
            "details" => $errorDetails ?? $response
        ]);
    }
    exit();
}

$responseData = json_decode($response, true);
$replyText = "";

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $replyText = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to get text response from Gemini API.", "raw" => $responseData]);
    exit();
}

echo json_encode(["response" => $replyText]);

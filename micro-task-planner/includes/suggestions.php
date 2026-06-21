<?php
// includes/suggestions.php — AJAX: AI-powered mini-task suggestions
// Calls the Anthropic API (claude-sonnet-4-6) to suggest mini-tasks for a given task title.

require_once 'auth.php';

header('Content-Type: application/json');
requireAuth();

$data  = json_decode(file_get_contents('php://input'), true);
$title = trim($data['title'] ?? '');

if (!$title) { echo json_encode(['suggestions' => []]); exit; }

// ── Call Anthropic API ────────────────────────────────
$apiKey = getenv('ANTHROPIC_API_KEY') ?: ''; // Set in your server environment

if (!$apiKey) {
    // Fallback: rule-based suggestions when API key not configured
    echo json_encode(['suggestions' => getFallbackSuggestions($title)]);
    exit;
}

$payload = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 300,
    'messages'   => [[
        'role'    => 'user',
        'content' => "Suggest exactly 4 short, actionable mini-tasks (steps) for this goal: \"$title\". " .
                     "Return ONLY a JSON array of strings, no explanation, no markdown. Example: [\"Step one\",\"Step two\"]"
    ]]
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 10,
]);

$response = curl_exec($ch);
curl_close($ch);

$result  = json_decode($response, true);
$text    = $result['content'][0]['text'] ?? '[]';
$text    = preg_replace('/```json|```/', '', $text);
$suggestions = json_decode(trim($text), true);

if (!is_array($suggestions)) $suggestions = getFallbackSuggestions($title);

echo json_encode(['suggestions' => array_slice($suggestions, 0, 5)]);

/* ── Fallback rule-based suggestions ────────────────── */
function getFallbackSuggestions(string $title): array {
    $t = strtolower($title);
    if (str_contains($t, 'study') || str_contains($t, 'learn'))
        return ['Skim the chapter to get an overview','Read carefully and take notes','Solve practice questions','Review mistakes and summarize key points'];
    if (str_contains($t, 'portfolio') || str_contains($t, 'website') || str_contains($t, 'web'))
        return ['Pick a domain name','Design the hero section','Write project case studies','Deploy to production'];
    if (str_contains($t, 'exercise') || str_contains($t, 'workout') || str_contains($t, 'gym'))
        return ['Warm up for 5 minutes','Complete main workout','Cool down and stretch','Log workout stats'];
    if (str_contains($t, 'read') || str_contains($t, 'book'))
        return ['Set a reading goal','Find a quiet reading spot','Read assigned pages','Write a short summary'];
    return ['Define the goal clearly','Research and gather resources','Start with the first step','Review progress and adjust'];
}

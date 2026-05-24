<?php
/**
 * G Theme - Content Translation API Proxy
 * 
 * Uses MyMemory free translation API to translate content.
 * Endpoint: POST with parameters: text, source, target
 * 
 * Supports: zh-CN (Simplified Chinese), zh-TW (Traditional Chinese), en (English)
 */

// Allow being called directly or from within Typecho
if (defined('__TYPECHO_ROOT_DIR__')) {
    // Called within Typecho context
} else {
    // Direct call - find Typecho root
    $dir = dirname(__FILE__);
    while ($dir !== '/' && !file_exists($dir . '/config.inc.php')) {
        $dir = dirname($dir);
    }
    if (file_exists($dir . '/config.inc.php')) {
        define('__TYPECHO_ROOT_DIR__', $dir);
    }
}

header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$text = isset($_POST['text']) ? $_POST['text'] : '';
$source = isset($_POST['source']) ? $_POST['source'] : 'zh-CN';
$target = isset($_POST['target']) ? $_POST['target'] : 'en';

if (empty($text)) {
    echo json_encode(['error' => 'No text provided', 'translated' => '']);
    exit;
}

// Map language codes to MyMemory format
$langMap = [
    'zh-CN' => 'zh-CN',
    'zh-TW' => 'zh-TW', 
    'en' => 'en',
];

$sourceLang = isset($langMap[$source]) ? $langMap[$source] : 'zh-CN';
$targetLang = isset($langMap[$target]) ? $langMap[$target] : 'en';

// If source and target are the same, return original
if ($sourceLang === $targetLang) {
    echo json_encode(['translated' => $text]);
    exit;
}

// Split text into chunks if too long (MyMemory has 500 char limit per request)
$maxChunkSize = 450;
$chunks = splitTextIntoChunks($text, $maxChunkSize);
$translatedChunks = [];

foreach ($chunks as $chunk) {
    $translated = translateChunk($chunk, $sourceLang, $targetLang);
    if ($translated === false) {
        echo json_encode(['error' => 'Translation API error', 'translated' => $text]);
        exit;
    }
    $translatedChunks[] = $translated;
}

echo json_encode(['translated' => implode('', $translatedChunks)]);
exit;

/**
 * Split text into chunks at sentence boundaries
 */
function splitTextIntoChunks($text, $maxSize) {
    if (mb_strlen($text, 'UTF-8') <= $maxSize) {
        return [$text];
    }
    
    $chunks = [];
    $sentences = preg_split('/(?<=[。！？.!?\n])/u', $text);
    $current = '';
    
    foreach ($sentences as $sentence) {
        if (mb_strlen($current . $sentence, 'UTF-8') > $maxSize && $current !== '') {
            $chunks[] = $current;
            $current = $sentence;
        } else {
            $current .= $sentence;
        }
    }
    
    if ($current !== '') {
        $chunks[] = $current;
    }
    
    return $chunks;
}

/**
 * Translate a single chunk using MyMemory API
 */
function translateChunk($text, $source, $target) {
    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $text,
        'langpair' => $source . '|' . $target,
        'de' => 'guest@example.com',
    ]);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'G-Theme/1.0',
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['responseData']['translatedText'])) {
        return $data['responseData']['translatedText'];
    }
    
    return false;
}

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

// Check if this is a cross-script translation (e.g. Chinese to English)
// For zh-CN <-> zh-TW, character substitution works fine with HTML
// For translations involving English, we need to extract text nodes to preserve HTML
$isCrossScript = ($sourceLang === 'en' || $targetLang === 'en');

if ($isCrossScript) {
    $translated = translateHtmlPreserving($text, $sourceLang, $targetLang);
    if ($translated === false) {
        echo json_encode(['error' => 'Translation API error', 'translated' => $text]);
        exit;
    }
    echo json_encode(['translated' => $translated]);
} else {
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
}
exit;

/**
 * Translate HTML content while preserving HTML structure.
 * Extracts text segments, translates them, and rebuilds HTML.
 */
function translateHtmlPreserving($html, $source, $target) {
    // Extract text segments from HTML (split by HTML tags)
    $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $textSegments = [];
    $textIndices = [];
    
    // Collect text segments (non-tag parts)
    for ($i = 0; $i < count($parts); $i++) {
        $part = $parts[$i];
        // Skip HTML tags and empty/whitespace-only segments
        if (empty($part) || $part[0] === '<') {
            continue;
        }
        $trimmed = trim($part);
        if ($trimmed !== '' && !ctype_space($part)) {
            $textSegments[] = $trimmed;
            $textIndices[] = $i;
        }
    }
    
    if (empty($textSegments)) {
        return $html;
    }
    
    // Batch translate: join segments with a unique delimiter
    $delimiter = ' ||| ';
    $maxChunkSize = 450;
    $translatedSegments = [];
    $batch = [];
    $batchLen = 0;
    
    for ($j = 0; $j < count($textSegments); $j++) {
        $seg = $textSegments[$j];
        $segLen = mb_strlen($seg, 'UTF-8');
        $delimLen = mb_strlen($delimiter, 'UTF-8');
        
        if ($batchLen > 0 && ($batchLen + $delimLen + $segLen) > $maxChunkSize) {
            // Translate current batch
            $batchText = implode($delimiter, $batch);
            $translated = translateChunk($batchText, $source, $target);
            if ($translated === false) {
                return false;
            }
            $splitResult = preg_split('/\s*\|\|\|\s*/', $translated);
            foreach ($splitResult as $s) {
                $translatedSegments[] = $s;
            }
            $batch = [$seg];
            $batchLen = $segLen;
        } else {
            $batch[] = $seg;
            $batchLen += ($batchLen > 0 ? $delimLen : 0) + $segLen;
        }
    }
    
    // Translate remaining batch
    if (!empty($batch)) {
        $batchText = implode($delimiter, $batch);
        $translated = translateChunk($batchText, $source, $target);
        if ($translated === false) {
            return false;
        }
        $splitResult = preg_split('/\s*\|\|\|\s*/', $translated);
        foreach ($splitResult as $s) {
            $translatedSegments[] = $s;
        }
    }
    
    // Rebuild HTML with translated text
    for ($k = 0; $k < count($textIndices); $k++) {
        $idx = $textIndices[$k];
        if (isset($translatedSegments[$k])) {
            // Preserve leading/trailing whitespace from original
            $original = $parts[$idx];
            $leadingSpace = '';
            $trailingSpace = '';
            if (preg_match('/^(\s+)/', $original, $m)) {
                $leadingSpace = $m[1];
            }
            if (preg_match('/(\s+)$/', $original, $m)) {
                $trailingSpace = $m[1];
            }
            $parts[$idx] = $leadingSpace . $translatedSegments[$k] . $trailingSpace;
        }
    }
    
    return implode('', $parts);
}

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

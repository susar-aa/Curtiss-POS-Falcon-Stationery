<?php
/**
 * Curtiss POS - Unsplash Wallpaper Cache API
 *
 * - API key stored server-side in config only (never exposed to JS)
 * - 1-hour cache in DB; returns cached image if within 1 hour
 * - Falls back gracefully if API key not set, quota exhausted, or network error
 * - No business data sent to Unsplash
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

// ─── Configuration ────────────────────────────────────────────────────────────
// Place your Unsplash Access Key in config/unsplash.php (see note below)
$unsplashKey = '';
$unsplashConfigFile = __DIR__ . '/config/unsplash.php';
if (file_exists($unsplashConfigFile)) {
    $unsplashKey = include $unsplashConfigFile;
}

$db  = Database::getInstance();
$pdo = $db->getConnection();

// ─── Check cache ─────────────────────────────────────────────────────────────
$cached = null;
try {
    $stmt = $pdo->query("SELECT * FROM nav_wallpaper_cache ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    // Table may not exist yet in local/live schema - fallback gracefully
    $cached = null;
}

$cacheValid = $cached &&
    isset($cached['fetched_at']) &&
    (time() - strtotime($cached['fetched_at'])) < 3600;

if ($cacheValid) {
    echo json_encode([
        'ok'           => true,
        'source'       => 'cache',
        'image_url'    => $cached['image_url'],
        'thumb_url'    => $cached['thumb_url'],
        'photographer' => $cached['photographer'],
    ]);
    exit;
}

// ─── No valid cache - fetch from Unsplash ─────────────────────────────────────
if (empty($unsplashKey)) {
    // No API key configured - return fallback signal
    echo json_encode(['ok' => true, 'source' => 'fallback', 'image_url' => null]);
    exit;
}

$query    = 'minimal architecture white clean workspace monochrome';
$apiUrl   = 'https://api.unsplash.com/photos/random'
          . '?query=' . urlencode($query)
          . '&orientation=landscape'
          . '&content_filter=high';

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'header'  => "Authorization: Client-ID {$unsplashKey}\r\nAccept: application/json\r\n",
        'timeout' => 5,
    ]
]);

$response = @file_get_contents($apiUrl, false, $ctx);

if ($response === false) {
    Logger::error('Unsplash API: network fetch failed');
    echo json_encode(['ok' => true, 'source' => 'fallback', 'image_url' => null]);
    exit;
}

$data = json_decode($response, true);

if (empty($data['urls']['regular'])) {
    Logger::error('Unsplash API: unexpected response', null, ['body' => substr($response, 0, 500)]);
    echo json_encode(['ok' => true, 'source' => 'fallback', 'image_url' => null]);
    exit;
}

$imageUrl     = $data['urls']['regular'];
$thumbUrl     = $data['urls']['thumb'] ?? null;
$photographer = ($data['user']['name'] ?? '');

// Store in cache (clear old rows first to keep table small)
try {
    $pdo->exec("TRUNCATE TABLE nav_wallpaper_cache");
    $ins = $pdo->prepare(
        "INSERT INTO nav_wallpaper_cache (image_url, thumb_url, photographer, fetched_at)
         VALUES (:img, :thumb, :photo, NOW())"
    );
    $ins->execute([':img' => $imageUrl, ':thumb' => $thumbUrl, ':photo' => $photographer]);
} catch (Throwable $e) {
    Logger::exception($e);
    // Non-fatal - still return the image
}

echo json_encode([
    'ok'           => true,
    'source'       => 'unsplash',
    'image_url'    => $imageUrl,
    'thumb_url'    => $thumbUrl,
    'photographer' => $photographer,
]);

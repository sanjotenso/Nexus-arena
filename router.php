<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/nexus-arena/static/(css|js)/[A-Za-z0-9._-]+$#', $path) === 1) {
    $assetPath = realpath(__DIR__ . $path);
    $staticRoot = realpath(__DIR__ . '/nexus-arena/static');
    if ($assetPath !== false
        && $staticRoot !== false
        && str_starts_with($assetPath, $staticRoot . DIRECTORY_SEPARATOR)
        && is_file($assetPath)
    ) {
        return false;
    }
}

require __DIR__ . '/index.php';

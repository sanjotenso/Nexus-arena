<?php
declare(strict_types=1);

session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/app/Core/helpers.php';
require_once __DIR__ . '/app/Models/Database.php';
require_once __DIR__ . '/app/Controllers/FrontController.php';

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/');

try {
    (new FrontController())->dispatch();
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'An unexpected server error occurred.';
}

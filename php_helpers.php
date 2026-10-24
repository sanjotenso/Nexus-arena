<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path): string
{
    global $basePath;
    return $basePath . '/' . ltrim($path, '/');
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function redirect(string $location): never
{
    header('Location: ' . app_url($location), true, 303);
    exit;
}

function method_not_allowed(): never
{
    http_response_code(405);
    header('Allow: GET, POST, DELETE');
    exit('Method Not Allowed');
}

function render_page(string $template, array $data, string $title): void
{
    global $basePath;
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/' . $template . '.php';
    $content = (string) ob_get_clean();
    require __DIR__ . '/base.php';
}

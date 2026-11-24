<?php

declare(strict_types=1);

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (PHP_SAPI === 'cli-server' && is_string($requestedPath)) {
    $publicRoot = realpath(__DIR__);
    $requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim($requestedPath, '/\\'));
    if (
        $publicRoot !== false
        && $requestedFile !== false
        && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)
    ) {
        return false;
    }
}

require_once __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? rtrim($path, '/') : '/';
$path = $path === '' ? '/' : $path;

if ($path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        \App\Config\Database::connection()->query('SELECT 1');
        echo json_encode(['status' => 'ok', 'database' => 'connected'], JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        error_log('Database health check failed: ' . $exception->getMessage());
        http_response_code(503);
        echo json_encode(['status' => 'error', 'database' => 'unavailable'], JSON_THROW_ON_ERROR);
    }
    exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

try {
    (new \App\Application())->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
} catch (Throwable $exception) {
    error_log(sprintf(
        'Application error [%s]: %s in %s:%d',
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    http_response_code(500);
    render('errors/server_error');
}

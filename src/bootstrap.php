<?php

declare(strict_types=1);

require_once __DIR__ . '/Config/Database.php';
require_once __DIR__ . '/Support/functions.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$environmentFile = dirname(__DIR__) . '/.env';
if (is_file($environmentFile) && is_readable($environmentFile)) {
    $environment = parse_ini_file($environmentFile, false, INI_SCANNER_RAW);
    if ($environment === false) {
        throw new RuntimeException('Unable to read the application .env file.');
    }
    foreach ($environment as $key => $value) {
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('foc_lost_found');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
} elseif (time() - (int) $_SESSION['last_activity'] > 1200) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('Your session expired. Please sign in again.', 'warning');
}
$_SESSION['last_activity'] = time();

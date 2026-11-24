<?php

declare(strict_types=1);

use App\Config\Database;

function db(): \PDO
{
    return Database::connection();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || !is_string($stored) || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(419);
        exit('The form expired. Return to the page and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        flash('Please sign in to continue.', 'warning');
        redirect('/login');
    }
    return $user;
}

function require_moderator(): array
{
    $user = require_auth();
    if (!in_array($user['role'], ['moderator', 'admin'], true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
    return $user;
}

function render(string $view, array $data = []): void
{
    $viewFile = dirname(__DIR__, 2) . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException("View {$view} does not exist.");
    }
    extract($data, EXTR_SKIP);
    $flashes = take_flashes();
    ob_start();
    try {
        require $viewFile;
        $viewContent = ob_get_clean();
    } catch (Throwable $exception) {
        ob_end_clean();
        throw $exception;
    }
    require dirname(__DIR__, 2) . '/views/layout.php';
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

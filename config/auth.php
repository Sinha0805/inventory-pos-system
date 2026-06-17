<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const ROLE_MINIMAL = 'minimal';
const ROLE_BASIC = 'basic';
const ROLE_ADVANCED = 'advanced';

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /inventory_pos/login.php');
        exit;
    }
}

function role_rank(string $role): int
{
    return [ROLE_MINIMAL => 1, ROLE_BASIC => 2, ROLE_ADVANCED => 3][$role] ?? 0;
}

function can_access(string $minimumRole): bool
{
    $user = current_user();
    return $user && role_rank($user['role']) >= role_rank($minimumRole);
}

function require_role(string $minimumRole): void
{
    require_login();
    if (!can_access($minimumRole)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            exit('Invalid CSRF token.');
        }
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = compact('message', 'type');
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}


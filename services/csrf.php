<?php
declare(strict_types=1);
/** Chống giả mạo yêu cầu (CSRF) cho các form POST ghi dữ liệu. */

function app_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

function csrf_token(): string {
    app_session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): bool {
    app_session_start();
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/** Thông báo một lần (hiện sau khi chuyển hướng POST → GET). */
function flash_set(string $type, string $message): void {
    app_session_start();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array {
    app_session_start();
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

<?php
declare(strict_types=1);
/** TODO: viết đăng nhập thật bằng session + password_verify khi triển khai backend. */
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(): void {
    // Trong bộ giao diện mẫu, các trang quản lý chỉ hiển thị dữ liệu giả và không cho ghi DB.
    // Khi có backend, phải bật bảo vệ server-side cho mọi chức năng ghi dữ liệu.
    if (current_user() === null && !demo_mode()) {
        header('Location: ' . url('auth/login.php')); exit;
    }
}

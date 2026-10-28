<?php
declare(strict_types=1);
/**
 * Xác thực bằng session + phân quyền theo vai trò.
 * - require_auth(): chưa đăng nhập → chuyển tới trang đăng nhập (kèm ?next= để quay lại).
 * - require_role([...]): sai vai trò → trang 403 “Bạn không có quyền thực hiện chức năng này.”
 * - require_module(key): kiểm tra theo bảng QUYEN_MODULE trong permissions.php.
 * Gọi các hàm này ở đầu trang, TRƯỚC khi xử lý POST.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/permissions.php';
require_once dirname(__DIR__) . '/services/csrf.php';
require_once dirname(__DIR__) . '/services/auth_service.php';

/** Không thao tác quá thời gian này (giây) thì tự đăng xuất. */
const PHIEN_HET_HAN_GIAY = 30 * 60;

function current_user(): ?array {
    static $user = false;
    if ($user !== false) return $user;
    app_session_start();
    $user = null;
    $ma = $_SESSION['user']['maTaiKhoan'] ?? null;
    if ($ma === null) return null;
    if (time() - (int)($_SESSION['last_seen'] ?? 0) > PHIEN_HET_HAN_GIAY) {
        dang_xuat();
        flash_set('info', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
        return null;
    }
    try {
        $user = layTaiKhoanHoatDong((string)$ma);
    } catch (Throwable $e) {
        error_log('current_user: ' . $e->getMessage());
        $user = $_SESSION['user'];        // mất kết nối CSDL tạm thời: giữ phiên, trang sẽ tự báo lỗi CSDL
    }
    if ($user === null) { dang_xuat(); return null; }
    $_SESSION['user'] = $user;
    $_SESSION['last_seen'] = time();
    return $user;
}

/** Ghi phiên sau khi đăng nhập thành công (đổi session id để chống chiếm phiên). */
function dang_nhap_phien(array $taiKhoan): void {
    app_session_start();
    session_regenerate_id(true);
    $_SESSION['user'] = $taiKhoan;
    $_SESSION['last_seen'] = time();
    unset($_SESSION['csrf_token']);
}

function dang_xuat(): void {
    app_session_start();
    unset($_SESSION['user'], $_SESSION['last_seen'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

/** Đường dẫn quay lại hợp lệ (chỉ trong ứng dụng, tránh chuyển hướng ra ngoài). */
function duong_dan_quay_lai(?string $next): string {
    $next = (string)$next;
    $goc = rtrim(base_path(), '/') . '/';
    if ($next !== '' && str_starts_with($next, $goc) && !str_contains($next, '//') && !str_contains($next, "\\") && !preg_match('/[\r\n]/', $next)) {
        return $next;
    }
    return url('admin/');
}

function require_auth(): array {
    $user = current_user();
    if ($user === null) {
        $next = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? '') : '';
        header('Location: ' . url('auth/login.php') . ($next !== '' ? '?next=' . rawurlencode($next) : ''), true, 302);
        exit;
    }
    return $user;
}

/** Hiện trang 403 trong layout quản trị và dừng. */
function tu_choi_truy_cap(string $active = ''): never {
    http_response_code(403);
    $page_title = 'Không có quyền truy cập'; $layout = 'dashboard';
    require __DIR__ . '/header.php';
    echo '<div class="ui-page"><div class="ui-notice ui-notice-error" role="alert"><span class="ui-notice-icon" aria-hidden="true">!</span><div><strong>'
       . e(TB_KHONG_CO_QUYEN) . '</strong><ul><li>Tài khoản ' . e(current_user()['tenDangNhap'] ?? '') . ' (' . e(ten_vai_tro(current_user()['vaiTro'] ?? '')) . ') không được dùng chức năng này.</li></ul></div></div>'
       . '<a class="btn btn-outline" href="' . e(url('admin/')) . '">← Về bảng điều khiển</a></div>';
    require __DIR__ . '/footer.php';
    exit;
}

/** Bắt buộc đăng nhập với một trong các vai trò. */
function require_role(array $vaiTro): array {
    $user = require_auth();
    if (!has_role($user, $vaiTro)) tu_choi_truy_cap();
    return $user;
}

/** Bắt buộc đăng nhập và được vào module (theo QUYEN_MODULE). */
function require_module(string $module): array {
    $user = require_auth();
    if (!can_access_module($user, $module)) tu_choi_truy_cap($module);
    return $user;
}

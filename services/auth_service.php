<?php
declare(strict_types=1);
/**
 * Đăng nhập, đổi mật khẩu (chức năng chung của mọi actor trong báo cáo giữa kỳ).
 * Bảng dùng: TaiKhoan. Mật khẩu băm bằng password_hash / kiểm tra bằng password_verify.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

const DN_SO_LAN_SAI_TOI_DA = 5;
const DN_PHUT_KHOA = 15;
const DN_MAT_KHAU_TOI_THIEU = 8;

const TB_DN_THIEU = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
const TB_DN_SAI = 'Tên đăng nhập hoặc mật khẩu không đúng.';
const TB_DN_BI_KHOA = 'Tài khoản đã bị khóa. Vui lòng liên hệ Quản trị viên hệ thống.';
const TB_DN_KHOA_TAM = 'Bạn đã nhập sai quá %d lần. Vui lòng thử lại sau %d phút.';
const TB_DN_LOI = 'Không thể đăng nhập lúc này. Vui lòng thử lại sau.';
const TB_DMK_THANH_CONG = 'Đổi mật khẩu thành công.';
const TB_DMK_KHONG_HOP_LE = 'Thông tin đổi mật khẩu không hợp lệ.';

/** Các cột đưa vào phiên đăng nhập (không gồm mật khẩu). */
function tk_cot_phien(): string {
    return 'maTaiKhoan, tenDangNhap, hoTen, vaiTro, maCanBo, maThiSinh, trangThai';
}

/**
 * Kiểm tra tên đăng nhập + mật khẩu. Trả về thông tin tài khoản (không có mật khẩu).
 * Sai DN_SO_LAN_SAI_TOI_DA lần liên tiếp thì khóa tạm DN_PHUT_KHOA phút.
 */
function dangNhap(string $tenDangNhap, string $matKhau): array {
    $tenDangNhap = trim($tenDangNhap);
    if ($tenDangNhap === '' || $matKhau === '') throw new BusinessException(TB_DN_THIEU);
    $pdo = db();
    $st = $pdo->prepare('SELECT matKhau, soLanSai, khoaDen, ' . tk_cot_phien() . ' FROM TaiKhoan WHERE tenDangNhap = ?');
    $st->execute([$tenDangNhap]);
    $tk = $st->fetch();
    if (!$tk) {
        password_verify($matKhau, password_hash('khong-ton-tai', PASSWORD_DEFAULT)); // thời gian xử lý giống khi có tài khoản
        throw new BusinessException(TB_DN_SAI);
    }
    if ($tk['trangThai'] !== 'Hoạt động') throw new BusinessException(TB_DN_BI_KHOA);
    if ($tk['khoaDen'] !== null && strtotime($tk['khoaDen']) > time()) {
        throw new BusinessException(sprintf(TB_DN_KHOA_TAM, DN_SO_LAN_SAI_TOI_DA - 1, DN_PHUT_KHOA));
    }
    if (!password_verify($matKhau, $tk['matKhau'])) {
        $sai = (int)$tk['soLanSai'] + 1;
        if ($sai >= DN_SO_LAN_SAI_TOI_DA) {
            $pdo->prepare('UPDATE TaiKhoan SET soLanSai = 0, khoaDen = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE maTaiKhoan = ?')->execute([DN_PHUT_KHOA, $tk['maTaiKhoan']]);
            throw new BusinessException(sprintf(TB_DN_KHOA_TAM, DN_SO_LAN_SAI_TOI_DA - 1, DN_PHUT_KHOA));
        }
        $pdo->prepare('UPDATE TaiKhoan SET soLanSai = ? WHERE maTaiKhoan = ?')->execute([$sai, $tk['maTaiKhoan']]);
        throw new BusinessException(TB_DN_SAI);
    }
    $pdo->prepare('UPDATE TaiKhoan SET soLanSai = 0, khoaDen = NULL, lanDangNhapCuoi = NOW() WHERE maTaiKhoan = ?')->execute([$tk['maTaiKhoan']]);
    if (password_needs_rehash($tk['matKhau'], PASSWORD_DEFAULT)) {
        $pdo->prepare('UPDATE TaiKhoan SET matKhau = ? WHERE maTaiKhoan = ?')->execute([password_hash($matKhau, PASSWORD_DEFAULT), $tk['maTaiKhoan']]);
    }
    unset($tk['matKhau'], $tk['soLanSai'], $tk['khoaDen']);
    return $tk;
}

/** Lấy lại tài khoản còn hoạt động (mỗi request) — tài khoản bị khóa sẽ bị đăng xuất ngay. */
function layTaiKhoanHoatDong(string $maTaiKhoan): ?array {
    $st = db()->prepare('SELECT ' . tk_cot_phien() . " FROM TaiKhoan WHERE maTaiKhoan = ? AND trangThai = 'Hoạt động'");
    $st->execute([$maTaiKhoan]);
    return $st->fetch() ?: null;
}

/** Đổi mật khẩu: kiểm tra mật khẩu hiện tại, mật khẩu mới đủ dài, khác mật khẩu cũ và nhập lại khớp. */
function doiMatKhau(string $maTaiKhoan, string $matKhauCu, string $matKhauMoi, string $nhapLai): void {
    $loi = [];
    $st = db()->prepare('SELECT matKhau FROM TaiKhoan WHERE maTaiKhoan = ?');
    $st->execute([$maTaiKhoan]);
    $hash = $st->fetchColumn();
    if ($hash === false || !password_verify($matKhauCu, (string)$hash)) $loi[] = 'Mật khẩu hiện tại không đúng.';
    if (mb_strlen($matKhauMoi, 'UTF-8') < DN_MAT_KHAU_TOI_THIEU) $loi[] = 'Mật khẩu mới phải có ít nhất ' . DN_MAT_KHAU_TOI_THIEU . ' ký tự.';
    elseif ($matKhauMoi === $matKhauCu) $loi[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    if ($matKhauMoi !== $nhapLai) $loi[] = 'Nhập lại mật khẩu mới không khớp.';
    if ($loi) throw new BusinessException(TB_DMK_KHONG_HOP_LE, $loi);
    db()->prepare('UPDATE TaiKhoan SET matKhau = ? WHERE maTaiKhoan = ?')->execute([password_hash($matKhauMoi, PASSWORD_DEFAULT), $maTaiKhoan]);
}

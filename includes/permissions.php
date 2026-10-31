<?php
declare(strict_types=1);
/**
 * Phân quyền theo vai trò (10 actor trong báo cáo giữa kỳ, mục "Xác định yêu cầu chức năng").
 * - VAI_TRO: mã vai trò → tên hiển thị (khớp CHECK của bảng TaiKhoan).
 * - QUYEN_MODULE: module trên sidebar → các vai trò được vào.
 * Trang có nhiều UC của các actor khác nhau thì tự gọi require_role([...]) cho đúng UC (xem auth.php).
 */

const VAI_TRO = [
    'HOC_SINH'       => 'Học sinh',
    'GVCN'           => 'Giáo viên chủ nhiệm lớp 9',
    'BAN_TUYEN_SINH' => 'Ban Tuyển sinh Sở GD&ĐT',
    'HD_TUYEN_SINH'  => 'Hội đồng tuyển sinh',
    'HD_COI_THI'     => 'Hội đồng coi thi',
    'HD_CHAM_THI'    => 'Hội đồng chấm thi',
    'CAT_PHACH'      => 'Bộ phận cắt phách',
    'HD_PHUC_KHAO'   => 'Hội đồng phúc khảo',
    'HD_RA_DE'       => 'Hội đồng ra đề thi',
    'QUAN_TRI'       => 'Quản trị viên hệ thống',
];

/** null = mọi tài khoản đã đăng nhập. */
const QUYEN_MODULE = [
    'admin'          => null,                                        // Bảng điều khiển
    'candidates'     => ['GVCN', 'BAN_TUYEN_SINH'],                  // Hồ sơ học sinh lớp 9, cấp SBD (UC3)
    'schools'        => ['BAN_TUYEN_SINH'],                          // Quản lý trường THCS, THPT
    'admission-info' => ['HOC_SINH', 'BAN_TUYEN_SINH'],              // UC1 xem thông tin tuyển sinh, giao chỉ tiêu
    'aspirations'    => ['HOC_SINH', 'HD_TUYEN_SINH'],               // UC2 quản lý nguyện vọng
    'exam'           => ['BAN_TUYEN_SINH', 'HD_RA_DE', 'HD_COI_THI'],// UC6, UC7 phòng thi; xem kỳ thi, môn thi
    'councils'       => ['BAN_TUYEN_SINH'],                          // UC10 quản lý Hội đồng thi
    'staff'          => ['BAN_TUYEN_SINH'],                          // Cán bộ
    'assignments'    => ['HD_COI_THI', 'HD_RA_DE'],                  // UC11 phân công coi thi, UC8 xem phân công ra đề
    'papers'         => ['CAT_PHACH'],                               // UC9 tra cứu lô bài thi, mã phách
    'grading'        => ['HD_CHAM_THI'],                             // UC5, UC12–UC14
    'appeals'        => ['HOC_SINH', 'HD_PHUC_KHAO'],                // UC16, UC18
    'admission'      => ['HD_TUYEN_SINH'],                           // UC17 xét tuyển
    'enrollment'     => ['HOC_SINH', 'HD_TUYEN_SINH'],               // UC19, UC20 nhập học
    'publication'    => ['BAN_TUYEN_SINH', 'HD_TUYEN_SINH'],         // UC15 công bố điểm, UC22 công bố kết quả tuyển sinh
    'statistics'     => ['BAN_TUYEN_SINH'],                          // UC21 thống kê tuyển sinh
];

const TB_KHONG_CO_QUYEN = 'Bạn không có quyền thực hiện chức năng này.';

function ten_vai_tro(string $vaiTro): string {
    return VAI_TRO[$vaiTro] ?? $vaiTro;
}

/** Tài khoản có một trong các vai trò? */
function has_role(?array $user, array $vaiTro): bool {
    return $user !== null && in_array($user['vaiTro'], $vaiTro, true);
}

/** Tài khoản được vào module (dùng cho sidebar và render_module). */
function can_access_module(?array $user, string $module): bool {
    if ($user === null || !array_key_exists($module, QUYEN_MODULE)) return false;
    $roles = QUYEN_MODULE[$module];
    return $roles === null || in_array($user['vaiTro'], $roles, true);
}

/** Giữ tương thích với code cũ: can('ten-module'). */
function can(string $permission): bool {
    return can_access_module(current_user(), $permission);
}

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
    'exam'           => ['BAN_TUYEN_SINH', 'HD_RA_DE', 'HD_COI_THI', 'QUAN_TRI'],// UC6, UC7 phòng thi; xem kỳ thi, môn thi
    'councils'       => ['BAN_TUYEN_SINH'],                          // UC10 quản lý Hội đồng thi
    'staff'          => ['BAN_TUYEN_SINH'],                          // Cán bộ
    'assignments'    => ['HD_COI_THI'],                  // UC11 phân công coi thi, UC8 xem phân công ra đề
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

/** Dashboard shortcuts follow module permissions and the exact destination's role checks. */
function dashboard_quick_links(?array $user): array {
    $links = [
        ['aspirations', 'aspirations/', 'Nguyện vọng', 'Xem danh sách nguyện vọng', '✧', null],
        ['candidates', 'candidates/', 'Thí sinh', 'Tra cứu hồ sơ thí sinh', '♙', null],
        ['admission-info', 'admission-info/', 'Thông tin tuyển sinh', 'Xem thông tin và chỉ tiêu', '▧', null],
        ['exam', 'exam/tao-phong.php', 'Tạo phòng thi', 'Quản lý thông tin phòng thi', '▣', ['BAN_TUYEN_SINH', 'QUAN_TRI']],
        ['exam', 'exam/phan-phong.php', 'Phân phòng thi', 'Sắp xếp thí sinh vào phòng thi', '▣', ['BAN_TUYEN_SINH', 'QUAN_TRI']],
        ['exam', 'exam/phan-cong-ra-de.php', 'Phân công ra đề', 'Xem nhiệm vụ ra đề thi', '▧', ['HD_RA_DE', 'BAN_TUYEN_SINH', 'QUAN_TRI']],
        ['assignments', 'assignments/', 'Phân công coi thi', 'Quản lý nhiệm vụ coi thi', '⇆', null],
        ['papers', 'papers/', 'Lô bài thi và số phách', 'Tra cứu thông tin lô bài thi', '▥', null],
        ['grading', 'grading/nhap-diem.php', 'Nhập điểm thi', 'Tải lô bài và nhập điểm', '✓', null],
        ['grading', 'grading/lich-su.php', 'Lịch sử chỉnh sửa điểm', 'Tra cứu các lần điều chỉnh', '↺', null],
        ['grading', 'grading/cap-nhat.php', 'Cập nhật điểm', 'Điều chỉnh kết quả chấm thi', '✓', null],
        ['grading', 'grading/thong-ke.php', 'Thống kê điểm', 'Theo dõi kết quả chấm thi', '▥', null],
        ['appeals', 'appeals/', 'Phúc khảo', 'Theo dõi yêu cầu phúc khảo', '↺', null],
        ['admission', 'admission/', 'Xét tuyển nguyện vọng', 'Xử lý kết quả xét tuyển', '◇', null],
        ['enrollment', 'enrollment/', 'Nhập học', 'Theo dõi tình trạng nhập học', '☑', null],
        ['publication', 'publication/diem-thi.php', 'Công bố điểm thi', 'Quản lý trạng thái công bố điểm', '◉', ['BAN_TUYEN_SINH']],
        ['publication', 'publication/tuyen-sinh.php', 'Công bố tuyển sinh', 'Quản lý kết quả tuyển sinh', '◉', ['HD_TUYEN_SINH']],
        ['councils', 'councils/', 'Hội đồng thi', 'Quản lý thông tin hội đồng', '♧', null],
        ['statistics', 'statistics/', 'Thống kê tuyển sinh', 'Theo dõi số liệu tuyển sinh', '▥', null],
    ];
    return array_values(array_filter($links, fn($link) => can_access_module($user, $link[0])
        && ($link[5] === null || has_role($user, $link[5]))));
}

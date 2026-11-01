<?php
declare(strict_types=1);

/**
 * UC21 - THỐNG KÊ KẾT QUẢ TUYỂN SINH
 * Nguyễn Thị Cẩm Vân
 * Actor: Ban Tuyển sinh Sở GD&ĐT
 *
 * Ba bộ lọc liên kết:
 * 1. Năm tuyển sinh
 * 2. Loại thống kê
 * 3. Tiêu chí chi tiết
 */

require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/admission_statistics_service.php';

require_role(['BAN_TUYEN_SINH']);

// ============================================
// 1. KHỞI TẠO
// ============================================

$danhSachNam = [];
$loaiThongKe = uc21_loaiThongKe();
$danhMucTieuChi = [];
$ketQua = [];
$nguon = [];

$loi = '';
$coLoiHeThong = false;

$namInput = trim((string)($_GET['nam'] ?? ''));
$loai = trim((string)($_GET['loai'] ?? ''));
$tieuChi = trim((string)($_GET['tieuChi'] ?? ''));

$hienThiTieuChi = false;
$daThongKe = false;

$tongHoSo = 0;
$tongDuThi = 0;
$tongTrungTuyen = 0;
$tongNhapHoc = 0;

$tyLeTrungTuyen = 0.0;
$tyLeNhapHoc = 0.0;

// ============================================
// 2. LẤY DỮ LIỆU
// ============================================

try {

    // Danh sách năm tuyển sinh
    $danhSachNam = uc21_layDanhSachNam();

    // Mặc định chọn năm mới nhất
    if ($namInput === '' && !empty($danhSachNam)) {
        $namInput = (string)$danhSachNam[0];
    }

    if ($namInput === '' || !ctype_digit($namInput)) {
        throw new InvalidArgumentException(
            'Vui lòng chọn năm tuyển sinh.'
        );
    }

    if (!in_array(
        $namInput,
        array_map('strval', $danhSachNam),
        true
    )) {
        throw new InvalidArgumentException(
            'Năm tuyển sinh không hợp lệ.'
        );
    }

    $nam = (int)$namInput;

    // Lấy dữ liệu nguồn thuộc năm đã chọn
    $nguon = uc21_duLieuNguon($nam);
    $dsDaDuThi = uc21_maThiSinhCoBaiThi($nam);

    // ========================================
    // 3. TÍNH TỔNG QUAN TOÀN NĂM
    // ========================================

    $hoSoIds = [];
    $trungTuyenIds = [];
    $nhapHocIds = [];

    foreach ($nguon as $row) {

        $ma = (string)$row['maThiSinh'];

        $hoSoIds[$ma] = true;

        if ($row['trangThaiXetTuyen'] === 'Trúng tuyển') {

            $trungTuyenIds[$ma] = true;

            if ($row['trangThaiNhapHoc'] === 'Đã xác nhận') {
                $nhapHocIds[$ma] = true;
            }
        }
    }

    $tongHoSo = count($hoSoIds);
    $tongDuThi = count(array_intersect_key($hoSoIds, $dsDaDuThi));
    $tongTrungTuyen = count($trungTuyenIds);
    $tongNhapHoc = count($nhapHocIds);

    $tyLeTrungTuyen = uc21_tinhTyLe(
        $tongTrungTuyen,
        $tongHoSo
    );

    $tyLeNhapHoc = uc21_tinhTyLe(
        $tongNhapHoc,
        $tongTrungTuyen
    );

    // ========================================
    // 4. LIÊN KẾT LOẠI VÀ TIÊU CHÍ
    // ========================================

    if ($loai !== '') {

        if (!isset($loaiThongKe[$loai])) {
            throw new InvalidArgumentException(
                'Loại thống kê không hợp lệ.'
            );
        }

        $hienThiTieuChi = true;

        // Lấy danh mục theo loại thống kê
        $danhMucGoc = uc21_danhMucTieuChi($loai);

        // Chỉ giữ trường có dữ liệu trong năm đã chọn
        if ($loai === 'thcs' || $loai === 'thpt') {

            $maCoDuLieu = [];

            foreach ($nguon as $row) {

                $ma = $loai === 'thcs'
                    ? (string)$row['maTruongTHCS']
                    : (string)$row['maTruongTHPT'];

                if ($ma !== '') {
                    $maCoDuLieu[$ma] = true;
                }
            }

            foreach ($danhMucGoc as $item) {
                if (isset($maCoDuLieu[(string)$item['ma']])) {
                    $danhMucTieuChi[] = $item;
                }
            }

        } else {

            $danhMucTieuChi = $danhMucGoc;
        }

        // Kiểm tra tiêu chí được chọn
        $maHopLe = array_map(
            fn($item) => (string)$item['ma'],
            $danhMucTieuChi
        );

        if (
            $tieuChi !== '' &&
            !in_array($tieuChi, $maHopLe, true)
        ) {
            // Không sử dụng tiêu chí cũ của loại/năm khác
            $tieuChi = '';
        }

        // Thống kê theo tổ hợp bộ lọc
        $ketQua = uc21_thongKe(
            $nam,
            $loai,
            $tieuChi
        );

        $daThongKe = true;
    }

} catch (InvalidArgumentException $e) {

    $loi = $e->getMessage();

} catch (Throwable $e) {

    error_log('UC21 Error: ' . $e->getMessage());

    $coLoiHeThong = true;

    $loi = 'Không thể thực hiện thống kê, vui lòng thử lại.';
}

// Xuất CSV chỉ khi thống kê hợp lệ; không tác động dữ liệu gốc.
if (($_GET['export'] ?? '') === 'csv') {
    if (!$daThongKe || $loi !== '') {
        http_response_code(400);
        exit('Không thể xuất báo cáo: bộ lọc hoặc dữ liệu không hợp lệ.');
    }
    $safeCell = static function ($value): string {
        $text = (string)$value;
        // Ngăn ứng dụng bảng tính diễn giải dữ liệu người dùng thành công thức.
        return preg_match('/^[\s]*[=+\-@]/u', $text) ? "'" . $text : $text;
    };
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="UC21_' . $nam . '_' . $loai . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['STT', 'Nhóm thống kê', 'Hồ sơ', 'Đủ điều kiện dự thi', 'Có bài thi', 'Trúng tuyển', 'Đã xác nhận nhập học', 'Chưa xác nhận', 'Không nhập học', 'Tỷ lệ trúng tuyển (%)', 'Tỷ lệ nhập học (%)']);
    foreach ($ketQua as $i => $item) {
        fputcsv($out, [
            $i + 1, $safeCell($item['tenNhom']), $item['soHoSo'],
            $item['soDuDieuKien'], $item['soDuThi'], $item['soTrungTuyen'],
            $item['soNhapHoc'], $item['soChuaXacNhan'], $item['soKhongNhapHoc'],
            $item['tyLeTrungTuyen'], $item['tyLeNhapHoc']
        ]);
    }
    fclose($out);
    exit;
}

// ============================================
// 5. HEADER CHUNG
// ============================================

ui_page_start(
    'statistics',
    'Thống kê kết quả tuyển sinh',
    'BAN TUYỂN SINH SỞ GD&ĐT',
    'Tổng hợp, phân tích và theo dõi số liệu tuyển sinh theo từng tiêu chí.',
    'UC21'
);
?>

<link
    rel="stylesheet"
    href="<?= e(url('assets/css/uc21-statistics.css')) ?>"
>

<div class="uc21-page">

    <!-- ====================================
         BỘ LỌC THỐNG KÊ
    ===================================== -->

    <section class="uc21-card">

        <div class="uc21-section-head">
            <h2>Bộ lọc thống kê</h2>

            <p>
                Chọn năm tuyển sinh, loại thống kê,
                sau đó chọn tiêu chí chi tiết tương ứng.
            </p>
        </div>

        <form
            method="GET"
            action="<?= e(url('statistics/')) ?>"
            class="uc21-filter"
            id="uc21FilterForm"
        >

            <!-- NĂM TUYỂN SINH -->
            <div class="uc21-field">

                <label for="nam">
                    Năm tuyển sinh *
                </label>

                <select
                    id="nam"
                    name="nam"
                    required
                >
                    <option value="">
                        -- Chọn năm --
                    </option>

                    <?php foreach ($danhSachNam as $namOption): ?>

                        <option
                            value="<?= e((string)$namOption) ?>"
                            <?= $namInput === (string)$namOption
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e((string)$namOption) ?>
                        </option>

                    <?php endforeach; ?>
                </select>

            </div>

            <!-- LOẠI THỐNG KÊ -->
            <div class="uc21-field">

                <label for="loai">
                    Loại thống kê *
                </label>

                <select
                    id="loai"
                    name="loai"
                    required
                >
                    <option value="">
                        -- Chọn loại thống kê --
                    </option>

                    <?php foreach ($loaiThongKe as $key => $label): ?>

                        <option
                            value="<?= e($key) ?>"
                            <?= $loai === $key ? 'selected' : '' ?>
                        >
                            <?= e($label) ?>
                        </option>

                    <?php endforeach; ?>
                </select>

            </div>

            <!-- TIÊU CHÍ CHI TIẾT -->
            <?php if ($hienThiTieuChi): ?>

                <div class="uc21-field">

                    <label for="tieuChi">
                        Tiêu chí chi tiết
                    </label>

                    <select
                        id="tieuChi"
                        name="tieuChi"
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <?php foreach ($danhMucTieuChi as $item): ?>

                            <option
                                value="<?= e((string)$item['ma']) ?>"
                                <?= $tieuChi === (string)$item['ma']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($item['ten']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            <?php endif; ?>

            <!-- NÚT THAO TÁC -->
            <div class="uc21-actions">

                <button
                    type="submit"
                    class="uc21-btn-primary"
                >
                    Thống kê
                </button>

                <a
                    href="<?= e(url('statistics/')) ?>"
                    class="uc21-btn-secondary"
                >
                    Làm mới
                </a>

            </div>

        </form>

    </section>

    <!-- THÔNG BÁO LỖI -->
    <?php if ($loi !== ''): ?>

        <div class="uc21-alert" role="alert">
            <?= e($loi) ?>
        </div>

    <?php endif; ?>

    <!-- ====================================
         TỔNG QUAN TOÀN NĂM
    ===================================== -->

    <section class="uc21-summary">

        <div class="uc21-summary-card">

            <span class="uc21-summary-label">Thí sinh có bài thi</span>
            <strong><?= number_format($tongDuThi, 0, ',', '.') ?></strong>
            <small>Ghi nhận trong năm <?= e($namInput) ?></small>

        </div>

        <div class="uc21-summary-card">

            <span class="uc21-summary-label">
                Hồ sơ có nguyện vọng
            </span>

            <strong>
                <?= number_format($tongHoSo, 0, ',', '.') ?>
            </strong>

            <small>Tổng quan toàn năm <?= e($namInput) ?></small>

        </div>

        <div class="uc21-summary-card">

            <span class="uc21-summary-label">
                Thí sinh trúng tuyển
            </span>

            <strong>
                <?= number_format($tongTrungTuyen, 0, ',', '.') ?>
            </strong>

            <small>
                Tỷ lệ:
                <?= number_format($tyLeTrungTuyen, 2) ?>%
            </small>

        </div>

        <div class="uc21-summary-card">

            <span class="uc21-summary-label">
                Đã xác nhận nhập học
            </span>

            <strong>
                <?= number_format($tongNhapHoc, 0, ',', '.') ?>
            </strong>

            <small>
                Tỷ lệ:
                <?= number_format($tyLeNhapHoc, 2) ?>%
            </small>

        </div>

        <div class="uc21-summary-card">

            <span class="uc21-summary-label">
                Năm tuyển sinh
            </span>

            <strong><?= e($namInput !== '' ? $namInput : '—') ?></strong>

            <small>Năm đang thống kê</small>

        </div>

    </section>

    <!-- ====================================
         BẢNG KẾT QUẢ CHI TIẾT
    ===================================== -->

    <section class="uc21-card">

        <div class="uc21-table-heading">

            <div>
                <h2>Kết quả thống kê chi tiết</h2>

                <p>
                    <?php if ($daThongKe): ?>

                        <?= e($loaiThongKe[$loai]) ?>
                        — Năm <?= e($namInput) ?>

                        <?php if ($tieuChi !== ''): ?>
                            — Đã lọc theo tiêu chí được chọn
                        <?php endif; ?>

                    <?php else: ?>

                        Chọn loại thống kê và nhấn Thống kê
                        để xem kết quả.

                    <?php endif; ?>
                </p>
            </div>

            <?php if ($daThongKe && !$coLoiHeThong): ?>
                <a class="uc21-btn-primary"
                   href="<?= e(url('statistics/') . '?' . http_build_query([
                       'nam' => $namInput, 'loai' => $loai,
                       'tieuChi' => $tieuChi, 'export' => 'csv'
                   ])) ?>">Xuất Excel (CSV)</a>
                <button
                    type="button"
                    class="uc21-btn-secondary"
                    onclick="window.print()"
                >
                    In báo cáo
                </button>
            <?php endif; ?>

        </div>

        <div class="uc21-table-wrap">

            
            <table class="uc21-table">

                <thead>
                    <tr>
                        <th>STT</th>

                        <?php if ($loai === 'nhaphoc'): ?>

                            <th>Tình trạng nhập học</th>
                            <th>Số thí sinh</th>

                        <?php elseif ($loai === 'ketqua'): ?>

                            <th>Kết quả xét tuyển</th>
                            <th>Số thí sinh</th>

                        <?php elseif ($loai === 'thpt'): ?>

                            <th>Trường THPT</th>
                            <th>Đăng ký</th>
                            <th>Có bài thi</th>
                            <th>Trúng tuyển</th>
                            <th>Đã nhập học</th>
                            <th>Chưa xác nhận</th>
                            <th>Không nhập học</th>
                            <th>Tỷ lệ trúng tuyển</th>
                            <th>Tỷ lệ nhập học</th>

                        <?php elseif ($loai === 'thcs'): ?>

                            <th>Trường THCS</th>
                            <th>Hồ sơ có nguyện vọng</th>
                            <th>Đủ điều kiện</th>
                            <th>Có bài thi</th>
                            <th>Trúng tuyển</th>
                            <th>Tỷ lệ trúng tuyển</th>

                        <?php elseif ($loai === 'nguyenvong'): ?>

                            <th>Nguyện vọng</th>
                            <th>Số thí sinh đăng ký</th>
                            <th>Có bài thi</th>
                            <th>Trúng tuyển</th>
                            <th>Đã nhập học</th>
                            <th>Tỷ lệ trúng tuyển</th>

                        <?php else: ?>

                            <th>Nhóm thống kê</th>
                            <th>Số lượng</th>

                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    $soCot = match ($loai) {
                        'nhaphoc', 'ketqua' => 3,
                        'thcs' => 7,
                        'nguyenvong' => 7,
                        'thpt' => 10,
                        default => 3
                    };
                    ?>

                    <?php if (!$daThongKe): ?>

                        <tr>
                            <td colspan="<?= $soCot ?>" class="uc21-empty">
                                Vui lòng chọn loại thống kê
                                và nhấn nút Thống kê.
                            </td>
                        </tr>

                    <?php elseif (empty($ketQua)): ?>

                        <tr>
                            <td colspan="<?= $soCot ?>" class="uc21-empty">
                                <?php if ($coLoiHeThong): ?>
                                    Không thể hiển thị kết quả thống kê.
                                <?php else: ?>
                                    Không có dữ liệu phù hợp
                                    với tiêu chí thống kê.
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($ketQua as $index => $item): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <?php if (
                                    $loai === 'nhaphoc' ||
                                    $loai === 'ketqua'
                                ): ?>

                                    <td class="uc21-group-name">
                                        <?= e($item['tenNhom']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $item['soHoSo'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                <?php elseif ($loai === 'thpt'): ?>

                                    <td class="uc21-group-name">
                                        <?= e($item['tenNhom']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $item['soHoSo'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td><?= number_format($item['soDuThi'], 0, ',', '.') ?></td>

                                    <td>
                                        <?= number_format(
                                            $item['soTrungTuyen'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $item['soNhapHoc'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td><?= number_format($item['soChuaXacNhan'], 0, ',', '.') ?></td>
                                    <td><?= number_format($item['soKhongNhapHoc'], 0, ',', '.') ?></td>

                                    <td>
                                        <span class="uc21-rate">
                                            <?= number_format(
                                                $item['tyLeTrungTuyen'], 2
                                            ) ?>%
                                        </span>
                                    </td>

                                    <td>
                                        <span class="uc21-rate">
                                            <?= number_format(
                                                $item['tyLeNhapHoc'], 2
                                            ) ?>%
                                        </span>
                                    </td>

                                <?php elseif ($loai === 'thcs'): ?>

                                    <td class="uc21-group-name">
                                        <?= e($item['tenNhom']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $item['soHoSo'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td><?= number_format($item['soDuDieuKien'], 0, ',', '.') ?></td>
                                    <td><?= number_format($item['soDuThi'], 0, ',', '.') ?></td>

                                    <td>
                                        <?= number_format(
                                            $item['soTrungTuyen'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="uc21-rate">
                                            <?= number_format(
                                                $item['tyLeTrungTuyen'], 2
                                            ) ?>%
                                        </span>
                                    </td>

                                <?php elseif ($loai === 'nguyenvong'): ?>

                                    <td class="uc21-group-name">
                                        <?= e($item['tenNhom']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $item['soHoSo'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td><?= number_format($item['soDuThi'], 0, ',', '.') ?></td>

                                    <td>
                                        <?= number_format(
                                            $item['soTrungTuyen'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $item['soNhapHoc'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="uc21-rate">
                                            <?= number_format(
                                                $item['tyLeTrungTuyen'], 2
                                            ) ?>%
                                        </span>
                                    </td>

                                <?php else: ?>

                                    <td><?= e($item['tenNhom']) ?></td>

                                    <td>
                                        <?= number_format(
                                            $item['soHoSo'], 0, ',', '.'
                                        ) ?>
                                    </td>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>


        </div>

    </section>

</div>

<!-- ==========================================
     ĐỒNG BỘ BA BỘ LỌC
========================================== -->
<script>
(function () {
    const form = document.getElementById('uc21FilterForm');
    const nam = document.getElementById('nam');
    const loai = document.getElementById('loai');
    const tieuChi = document.getElementById('tieuChi');

    if (!form || !nam || !loai) return;

    const namBanDau = nam.value;
    const loaiBanDau = loai.value;

    // Khi đổi năm hoặc loại, tiêu chí cũ không còn hợp lệ.
    function xoaTieuChiCu() {
        if (tieuChi) {
            tieuChi.value = '';
        }
    }

    nam.addEventListener('change', xoaTieuChiCu);
    loai.addEventListener('change', xoaTieuChiCu);

    // Trước khi submit, loại bỏ tiêu chí thuộc năm/loại cũ.
    form.addEventListener('submit', function () {
        if (
            tieuChi &&
            (nam.value !== namBanDau || loai.value !== loaiBanDau)
        ) {
            tieuChi.value = '';
        }
    });
})();
</script>

<?php ui_page_end(); ?>

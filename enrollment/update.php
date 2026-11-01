<?php
declare(strict_types=1);

/**
 * UC20 - CẬP NHẬT TÌNH TRẠNG NHẬP HỌC
 * Nguyễn Thị Cẩm Vân
 */

require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/enrollment_update_service.php';

$user = require_role(['HD_TUYEN_SINH']);

$maThiSinh = trim((string)($_GET['maThiSinh'] ?? ''));
$soBaoDanh = trim((string)($_GET['soBaoDanh'] ?? ''));
$maTruong = trim((string)($_GET['maTruong'] ?? ''));
$maDangChon = trim((string)($_GET['maTinhTrang'] ?? ''));

$loi = '';
$danhSach = [];
$danhSachTruong = [];
$lichSu = [];
$thiSinhDuocChon = null;
$coLoiHeThong = false;

// ==========================================
// 1. XỬ LÝ CẬP NHẬT
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $maDangChon = ui_input('maTinhTrang');
    $loiCsrf = ui_csrf_error();

    if ($loiCsrf !== null) {
        $loi = $loiCsrf;
    } else {
        try {
            $trangThaiMoi = ui_input('trangThaiMoi');

            uc20_capNhatTinhTrang(
                $maDangChon,
                $trangThaiMoi,
                (string)$user['tenDangNhap']
            );

            flash_set(
                'success',
                'Cập nhật tình trạng nhập học thành công.'
            );

            ui_redirect('enrollment/update.php', [
                'maTinhTrang' => $maDangChon
            ]);

        } catch (InvalidArgumentException $e) {
            $loi = $e->getMessage();

        } catch (RuntimeException $e) {
            $loi = $e->getMessage();

        } catch (Throwable $e) {
            error_log('UC20 update: ' . $e->getMessage());
            $loi = 'Không thể cập nhật tình trạng nhập học, vui lòng thử lại.';
        }
    }
}

// ==========================================
// 2. LẤY DANH SÁCH THÍ SINH
// ==========================================

try {
    $danhSachTruong = uc20_layDanhSachTruong();

    $danhSach = uc20_layDanhSach(
        $maThiSinh,
        $soBaoDanh,
        $maTruong
    );

    if ($maDangChon !== '') {

        foreach ($danhSach as $row) {
            if ($row['maTinhTrang'] === $maDangChon) {
                $thiSinhDuocChon = $row;
                break;
            }
        }

        if ($thiSinhDuocChon !== null) {
            $lichSu = uc20_layLichSu($maDangChon);
        } elseif ($loi === '') {
            $loi = 'Không tìm thấy thông tin thí sinh phù hợp.';
        }
    }

} catch (InvalidArgumentException $e) {
    $loi = $e->getMessage();

} catch (Throwable $e) {
    error_log('UC20 load: ' . $e->getMessage());
    $coLoiHeThong = true;
    $loi = 'Không thể tải dữ liệu nhập học.';
}

// ==========================================
// 3. HIỂN THỊ GIAO DIỆN
// ==========================================

ui_page_start(
    'enrollment',
    'Cập nhật tình trạng nhập học',
    'HỘI ĐỒNG TUYỂN SINH',
    'Tra cứu, xác nhận và cập nhật tình trạng nhập học của thí sinh.',
    'UC20'
);
?>

<link
    rel="stylesheet"
    href="<?= e(url('assets/css/uc20-enrollment.css')) ?>"
>

<div class="uc20-page">

    <?php ui_flashes(); ?>

    <?php if ($loi !== ''): ?>
        <div class="uc20-alert" role="alert">
            <?= e($loi) ?>
        </div>
    <?php endif; ?>

    <!-- ======================================
         4. BỘ LỌC TRA CỨU
    ======================================= -->

    <section class="uc20-card">

        <div class="uc20-card-head">
            <h2>Tra cứu thí sinh</h2>
            <p>
                Nhập mã thí sinh, số báo danh hoặc
                chọn trường THPT để tìm kiếm.
            </p>
        </div>

        <form
            method="GET"
            action="<?= e(url('enrollment/update.php')) ?>"
        >

            <div class="uc20-search-grid">

                <div class="uc20-field">
                    <label for="maThiSinh">
                        Mã thí sinh
                    </label>

                    <input
                        type="text"
                        id="maThiSinh"
                        name="maThiSinh"
                        maxlength="20"
                        pattern="[A-Za-z0-9_-]{1,20}"
                        placeholder="Nhập mã thí sinh..."
                        value="<?= e($maThiSinh) ?>"
                    >
                </div>

                <div class="uc20-field">
                    <label for="soBaoDanh">
                        Số báo danh
                    </label>

                    <input
                        type="text"
                        id="soBaoDanh"
                        name="soBaoDanh"
                        maxlength="20"
                        pattern="[A-Za-z0-9_-]{1,20}"
                        placeholder="Nhập số báo danh..."
                        value="<?= e($soBaoDanh) ?>"
                    >
                </div>

                <div class="uc20-field">
                    <label for="maTruong">
                        Trường THPT
                    </label>

                    <select
                        id="maTruong"
                        name="maTruong"
                    >
                        <option value="">
                            Tất cả trường THPT
                        </option>

                        <?php foreach ($danhSachTruong as $truong): ?>
                            <option
                                value="<?= e($truong['maTruongTHPT']) ?>"
                                <?= $maTruong === $truong['maTruongTHPT']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($truong['tenTruongTHPT']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="uc20-actions">

                <button
                    type="submit"
                    class="uc20-btn-primary"
                >
                    Tìm kiếm
                </button>

                <a
                    href="<?= e(url('enrollment/update.php')) ?>"
                    class="uc20-btn-secondary"
                >
                    Làm mới
                </a>

            </div>

        </form>

    </section>

    <!-- ======================================
         5. DANH SÁCH THÍ SINH
    ======================================= -->

    <section class="uc20-card">

        <div class="uc20-card-head">

            <h2>Danh sách thí sinh trúng tuyển</h2>

            <p>
                Tổng số:
                <strong><?= count($danhSach) ?></strong>
                thí sinh phù hợp.
            </p>

        </div>

        <div class="uc20-table-wrap">

            <table class="uc20-table">

                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã thí sinh</th>
                        <th>Họ và tên</th>
                        <th>Số báo danh</th>
                        <th>Trường THPT</th>
                        <th>Trạng thái hiện tại</th>
                        <th>Thời gian xác nhận</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($danhSach)): ?>

                        <tr>
                            <td colspan="8" class="uc20-empty">

                                <?php if ($coLoiHeThong): ?>

                                    Không thể tải dữ liệu nhập học.

                                <?php elseif ($loi !== ''): ?>

                                    Vui lòng kiểm tra lại
                                    tiêu chí tìm kiếm.

                                <?php elseif (
                                    $maThiSinh !== '' ||
                                    $soBaoDanh !== '' ||
                                    $maTruong !== ''
                                ): ?>

                                    Không tìm thấy thông tin
                                    thí sinh phù hợp.

                                <?php else: ?>

                                    Chưa có thí sinh trúng tuyển
                                    đủ điều kiện cập nhật.

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($danhSach as $index => $row): ?>

                            <?php
                            $status = $row['trangThaiNhapHoc'];

                            $statusClass = match ($status) {
                                'Đã xác nhận' => 'success',
                                'Không nhập học' => 'danger',
                                default => 'pending'
                            };

                            $query = http_build_query([
                                'maThiSinh' => $maThiSinh,
                                'soBaoDanh' => $soBaoDanh,
                                'maTruong' => $maTruong,
                                'maTinhTrang' => $row['maTinhTrang']
                            ]);
                            ?>

                            <tr>

                                <td><?= $index + 1 ?></td>

                                <td><?= e($row['maThiSinh']) ?></td>

                                <td><?= e($row['hoTen']) ?></td>

                                <td><?= e($row['soBaoDanh'] ?? '—') ?></td>

                                <td><?= e($row['tenTruongTHPT'] ?? '—') ?></td>

                                <td>
                                    <span
                                        class="uc20-status uc20-<?= e($statusClass) ?>"
                                    >
                                        <?= e($status) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($row['thoiGianXacNhanNhapHoc'])
                                        ? e(ui_fmt_time(
                                            $row['thoiGianXacNhanNhapHoc']
                                        ))
                                        : '—' ?>
                                </td>

                                <td>
                                    <a
                                        class="uc20-action-link"
                                        href="<?= e(
                                            url('enrollment/update.php') .
                                            '?' . $query
                                        ) ?>"
                                    >
                                        Chọn cập nhật
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- ======================================
         6. FORM CẬP NHẬT
    ======================================= -->

    <?php if ($thiSinhDuocChon !== null): ?>

        <section class="uc20-card">

            <div class="uc20-card-head">
                <h2>Cập nhật tình trạng nhập học</h2>

                <p>
                    Kiểm tra thông tin thí sinh
                    trước khi xác nhận cập nhật.
                </p>
            </div>

            <div class="uc20-current-info">

                <p>
                    <strong>Họ và tên</strong>
                    <?= e($thiSinhDuocChon['hoTen']) ?>
                </p>

                <p>
                    <strong>Mã thí sinh</strong>
                    <?= e($thiSinhDuocChon['maThiSinh']) ?>
                </p>

                <p>
                    <strong>Số báo danh</strong>
                    <?= e($thiSinhDuocChon['soBaoDanh'] ?? '—') ?>
                </p>

                <p>
                    <strong>Trường trúng tuyển</strong>
                    <?= e($thiSinhDuocChon['tenTruongTHPT'] ?? '—') ?>
                </p>

                <p>
                    <strong>Trạng thái hiện tại</strong>
                    <?= e($thiSinhDuocChon['trangThaiNhapHoc']) ?>
                </p>

            </div>

            <form
                method="POST"
                action="<?= e(url('enrollment/update.php')) ?>"
                onsubmit="return confirm('Bạn có chắc muốn cập nhật tình trạng nhập học của thí sinh này?');"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="maTinhTrang"
                    value="<?= e($thiSinhDuocChon['maTinhTrang']) ?>"
                >

                <div class="uc20-form-grid">

                    <div class="uc20-field">

                        <label for="trangThaiMoi">
                            Trạng thái nhập học mới *
                        </label>

                        <select
                            id="trangThaiMoi"
                            name="trangThaiMoi"
                            required
                        >
                            <option value="">
                                -- Chọn trạng thái --
                            </option>

                            <option value="Chưa xác nhận">
                                Chưa xác nhận
                            </option>

                            <option value="Đã xác nhận">
                                Đã xác nhận
                            </option>

                            <option value="Không nhập học">
                                Không nhập học
                            </option>
                        </select>

                    </div>

                </div>

                <div class="uc20-actions">

                    <button
                        type="submit"
                        class="uc20-btn-primary"
                    >
                        Xác nhận cập nhật
                    </button>

                    <a
                        href="<?= e(url('enrollment/update.php')) ?>"
                        class="uc20-btn-outline"
                    >
                        Hủy
                    </a>

                </div>

            </form>

        </section>

        <!-- ==================================
             7. LỊCH SỬ CẬP NHẬT
        =================================== -->

        <section class="uc20-card">

            <h2>Lịch sử cập nhật</h2>

            <div class="uc20-table-wrap">

                <table class="uc20-table">

                    <thead>
                        <tr>
                            <th>Mã cập nhật</th>
                            <th>Nội dung thay đổi</th>
                            <th>Thời gian</th>
                            <th>Người cập nhật</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($lichSu)): ?>

                            <tr>
                                <td colspan="4" class="uc20-empty">
                                    Chưa có lịch sử cập nhật.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($lichSu as $item): ?>

                                <tr>
                                    <td>
                                        <?= e($item['maCapNhat']) ?>
                                    </td>

                                    <td>
                                        <?= e($item['noiDungCapNhat']) ?>
                                    </td>

                                    <td>
                                        <?= e(ui_fmt_time(
                                            $item['thoiGianCapNhat']
                                        )) ?>
                                    </td>

                                    <td>
                                        <?= e($item['nguoiCapNhat'] ?? '—') ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    <?php endif; ?>

</div>

<?php ui_page_end(); ?>

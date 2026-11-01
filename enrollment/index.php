<?php
declare(strict_types=1);

/**
 * UC19 - Xem tình trạng nhập học
 * Nguyễn Thị Cẩm Vân
 */

require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/enrollment_service.php';

require_role(['HD_TUYEN_SINH']);

$danhSach = [];
$danhSachNam = [];
$danhSachTruong = [];
$chiTiet = null;
$loi = '';
$coLoiHeThong = false;

// Lấy tiêu chí từ GET
$namInput = trim((string)($_GET['nam'] ?? ''));
$maThiSinh = trim((string)($_GET['maThiSinh'] ?? ''));
$soBaoDanh = trim((string)($_GET['soBaoDanh'] ?? ''));
$maTruong = trim((string)($_GET['maTruong'] ?? ''));
$trangThai = trim((string)($_GET['trangThai'] ?? ''));
$maKetQua = trim((string)($_GET['maKetQua'] ?? ''));

try {
    $danhSachNam = uc19_layDanhSachNam();
    $danhSachTruong = uc19_layDanhSachTruong();

    // Mặc định chọn năm mới nhất.
    if ($namInput === '' && !empty($danhSachNam)) {
        $namInput = (string)$danhSachNam[0];
    }

    if ($namInput === '' || !ctype_digit($namInput)) {
        throw new InvalidArgumentException(
            'Vui lòng chọn năm tuyển sinh.'
        );
    }

    $nam = (int)$namInput;

    if (!in_array($namInput, array_map('strval', $danhSachNam), true)) {
        throw new InvalidArgumentException(
            'Năm tuyển sinh không hợp lệ.'
        );
    }

    $danhSach = layDanhSachTinhTrangNhapHoc(
        $nam,
        $maThiSinh,
        $soBaoDanh,
        $maTruong,
        $trangThai
    );

    if ($maKetQua !== '') {
        $chiTiet = layChiTietTinhTrangNhapHoc(
            $maKetQua,
            $nam
        );

        if ($chiTiet === null) {
            $loi = 'Không tìm thấy thông tin thí sinh phù hợp với tiêu chí tra cứu.';
        }
    }

} catch (InvalidArgumentException $e) {
    $loi = $e->getMessage();

} catch (Throwable $e) {
    error_log('UC19 Error: ' . $e->getMessage());
    $coLoiHeThong = true;
    $loi = 'Không thể truy xuất tình trạng nhập học. Vui lòng thử lại.';
}

ui_page_start(
    'enrollment',
    'Xem tình trạng nhập học',
    'HỘI ĐỒNG TUYỂN SINH',
    'Tra cứu kết quả tuyển sinh và tình trạng nhập học theo các tiêu chí.',
    'UC19'
);
?>

<link rel="stylesheet"
      href="<?= e(url('assets/css/uc19-enrollment.css')) ?>">

<div class="uc19-page">

    <!-- BỘ LỌC -->
    <section class="uc19-card">
        <h2>Bộ lọc tra cứu</h2>
        <p class="uc19-description">
            Chọn năm tuyển sinh và nhập các tiêu chí cần tra cứu.
        </p>

        <form method="GET"
              action="<?= e(url('enrollment/')) ?>"
              class="uc19-filter uc19-filter-new">

            <div class="uc19-field">
                <label for="nam">Năm tuyển sinh *</label>
                <select name="nam" id="nam" required>
                    <option value="">-- Chọn năm --</option>
                    <?php foreach ($danhSachNam as $namOption): ?>
                        <option value="<?= e((string)$namOption) ?>"
                            <?= $namInput === (string)$namOption ? 'selected' : '' ?>>
                            <?= e((string)$namOption) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="uc19-field">
                <label for="maTruong">Trường THPT</label>
                <select name="maTruong" id="maTruong">
                    <option value="">Tất cả trường THPT</option>
                    <?php foreach ($danhSachTruong as $truong): ?>
                        <option
                            value="<?= e($truong['maTruongTHPT']) ?>"
                            <?= $maTruong === $truong['maTruongTHPT']
                                ? 'selected' : '' ?>>
                            <?= e($truong['tenTruongTHPT']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="uc19-field">
                <label for="maThiSinh">Mã thí sinh</label>
                <input
                    type="text"
                    name="maThiSinh"
                    id="maThiSinh"
                    maxlength="20"
                    pattern="[A-Za-z0-9_-]{1,20}"
                    title="Chỉ nhập chữ, số, dấu gạch ngang hoặc gạch dưới."
                    placeholder="Nhập mã thí sinh..."
                    value="<?= e($maThiSinh) ?>">
            </div>

            <div class="uc19-field">
                <label for="soBaoDanh">Số báo danh</label>
                <input
                    type="text"
                    name="soBaoDanh"
                    id="soBaoDanh"
                    maxlength="20"
                    pattern="[A-Za-z0-9_-]{1,20}"
                    title="Chỉ nhập chữ, số, dấu gạch ngang hoặc gạch dưới."
                    placeholder="Nhập số báo danh..."
                    value="<?= e($soBaoDanh) ?>">
            </div>

            <div class="uc19-field">
                <label for="trangThai">Trạng thái nhập học</label>
                <select name="trangThai" id="trangThai">
                    <?php
                    $options = [
                        '' => 'Tất cả trạng thái',
                        'Chưa xác nhận' => 'Chưa xác nhận',
                        'Đã xác nhận' => 'Đã xác nhận',
                        'Không nhập học' => 'Không nhập học'
                    ];
                    foreach ($options as $value => $label):
                    ?>
                        <option value="<?= e($value) ?>"
                            <?= $trangThai === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="uc19-actions">
                <button type="submit" class="uc19-btn-primary">
                    Tìm kiếm
                </button>
                <a href="<?= e(url('enrollment/')) ?>"
                   class="uc19-btn-secondary">
                    Làm mới
                </a>
            </div>
        </form>
    </section>

    <?php if ($loi !== ''): ?>
        <div class="uc19-alert" role="alert">
            <?= e($loi) ?>
        </div>
    <?php endif; ?>

    <!-- DANH SÁCH -->
    <section class="uc19-card">
        <div class="uc19-table-heading">
            <div>
                <h2>Danh sách tình trạng nhập học</h2>
                <p>
                    Tổng số:
                    <strong><?= e((string)count($danhSach)) ?></strong>
                    kết quả
                </p>
            </div>
        </div>

        <div class="uc19-table-wrapper">
            <table class="uc19-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã thí sinh</th>
                        <th>Họ và tên</th>
                        <th>Số báo danh</th>
                        <th>Trường THPT</th>
                        <th>Điểm xét tuyển</th>
                        <th>Kết quả</th>
                        <th>Nhập học</th>
                        <th>Thời gian xác nhận</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($danhSach)): ?>
                    <tr>
                        <td colspan="10" class="uc19-empty">
                            <?php if ($coLoiHeThong): ?>
                                Không thể tải dữ liệu tuyển sinh.
                            <?php elseif ($loi !== ''): ?>
                                Vui lòng kiểm tra lại tiêu chí tra cứu.
                            <?php elseif (
                                $maThiSinh !== '' ||
                                $soBaoDanh !== '' ||
                                $maTruong !== '' ||
                                $trangThai !== ''
                            ): ?>
                                Không tìm thấy thông tin thí sinh
                                phù hợp với tiêu chí tra cứu.
                            <?php else: ?>
                                Chưa có kết quả tuyển sinh được công bố
                                hoặc chưa có dữ liệu phù hợp.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSach as $i => $row): ?>
                        <?php
                        $ketQua = $row['trangThaiXetTuyen'];

                        $nhapHoc = $ketQua === 'Không trúng tuyển'
                            ? 'Không áp dụng'
                            : ($row['trangThaiNhapHoc'] ?? 'Chưa ghi nhận');

                        $statusClass = match ($nhapHoc) {
                            'Đã xác nhận' => 'success',
                            'Không nhập học' => 'danger',
                            default => 'pending'
                        };

                        $query = http_build_query([
                            'nam' => $namInput,
                            'maThiSinh' => $maThiSinh,
                            'soBaoDanh' => $soBaoDanh,
                            'maTruong' => $maTruong,
                            'trangThai' => $trangThai,
                            'maKetQua' => $row['maKetQua']
                        ]);
                        ?>
                        <tr>
                            <td><?= e((string)($i + 1)) ?></td>
                            <td><?= e($row['maThiSinh']) ?></td>
                            <td><?= e($row['hoTen']) ?></td>
                            <td><?= e($row['soBaoDanh'] ?? '—') ?></td>
                            <td><?= e($row['tenTruongTHPT'] ?? '—') ?></td>
                            <td>
                                
                                <?= e(
                                    $row['diemXetTuyen'] === null
                                        ? '—'
                                        : number_format(
                                            (float)$row['diemXetTuyen'],
                                            2
                                        )
                                ) ?>

                                    ? '—'
                                    : number_format(
                                        (float)$row['diemXetTuyen'], 2
                                    ) ?>
                            </td>
                            <td><?= e($ketQua) ?></td>
                            <td>
                                <span class="uc19-status uc19-<?= e($statusClass) ?>">
                                    <?= e($nhapHoc) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($row['thoiGianXacNhanNhapHoc'])
                                    ? e(ui_fmt_time($row['thoiGianXacNhanNhapHoc']))
                                    : '—' ?>
                            </td>
                            <td>
                                <a class="uc19-btn-secondary"
                                   href="<?= e(url('enrollment/') . '?' . $query) ?>">
                                    Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- CHI TIẾT -->
    <?php if ($chiTiet !== null): ?>
        <?php
        $ketQuaChiTiet = $chiTiet['trangThaiXetTuyen'];
        $nhapHocChiTiet = $ketQuaChiTiet === 'Không trúng tuyển'
            ? 'Không áp dụng'
            : ($chiTiet['trangThaiNhapHoc'] ?? 'Chưa ghi nhận');
        ?>
        <section class="uc19-card">
            <h2>Chi tiết tình trạng nhập học</h2>

            <div class="uc19-detail">
                <p><strong>Năm tuyển sinh</strong>
                    <?= e((string)$chiTiet['namTuyenSinh']) ?></p>

                <p><strong>Mã thí sinh</strong>
                    <?= e($chiTiet['maThiSinh']) ?></p>

                <p><strong>Họ và tên</strong>
                    <?= e($chiTiet['hoTen']) ?></p>

                <p><strong>Số báo danh</strong>
                    <?= e($chiTiet['soBaoDanh'] ?? '—') ?></p>

                <p><strong>Trường THPT</strong>
                    <?= e($chiTiet['tenTruongTHPT'] ?? '—') ?></p>

                <p><strong>Điểm xét tuyển</strong>
                    
                <?= e(
                    $chiTiet['diemXetTuyen'] === null
                        ? '—'
                        : number_format(
                            (float)$chiTiet['diemXetTuyen'],
                            2
                        )
                ) ?>

                        ? '—'
                        : number_format(
                            (float)$chiTiet['diemXetTuyen'], 2
                        ) ?></p>

                <p><strong>Kết quả xét tuyển</strong>
                    <?= e($ketQuaChiTiet) ?></p>

                <p><strong>Trạng thái nhập học</strong>
                    <?= e($nhapHocChiTiet) ?></p>

                <p><strong>Thời gian xác nhận</strong>
                    <?= !empty($chiTiet['thoiGianXacNhanNhapHoc'])
                        ? e(ui_fmt_time($chiTiet['thoiGianXacNhanNhapHoc']))
                        : '—' ?></p>
            </div>

            <a class="uc19-btn-secondary"
               href="<?= e(url('enrollment/') . '?' . http_build_query([
                   'nam' => $namInput,
                   'maThiSinh' => $maThiSinh,
                   'soBaoDanh' => $soBaoDanh,
                   'maTruong' => $maTruong,
                   'trangThai' => $trangThai
               ])) ?>">
                Đóng chi tiết
            </a>
        </section>
    <?php endif; ?>

</div>

<?php ui_page_end(); ?>

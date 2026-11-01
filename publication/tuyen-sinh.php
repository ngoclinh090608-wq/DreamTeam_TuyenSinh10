<?php
declare(strict_types=1);

/** UC22 - Công bố kết quả tuyển sinh - Nguyễn Thị Cẩm Vân. */
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/admission_publication_service.php';
require_role(['HD_TUYEN_SINH']);

$namInput = trim((string)($_GET['nam'] ?? ''));
$maDanhSach = trim((string)($_GET['maDanhSach'] ?? ''));
$xacNhan = ($_GET['xacNhan'] ?? '') === '1';
$dsNam = $danhSach = [];
$chiTiet = null;
$dieuKien = [];
$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loiCsrf = ui_csrf_error();
    if ($loiCsrf !== null) {
        $loi = $loiCsrf;
    } elseif (($_POST['action'] ?? '') === 'cong_bo') {
        $maPost = trim((string)($_POST['maDanhSach'] ?? ''));
        try {
            uc22_congBo($maPost);
            flash_set('success', 'Công bố kết quả tuyển sinh thành công.');
            ui_redirect('publication/tuyen-sinh.php', ['maDanhSach' => $maPost]);
        } catch (DomainException | InvalidArgumentException $e) {
            $loi = $e->getMessage();
            $maDanhSach = $maPost;
        } catch (Throwable $e) {
            error_log('UC22 POST: ' . $e->getMessage());
            $loi = 'Không thể công bố kết quả. Dữ liệu chưa thay đổi, vui lòng thử lại.';
        }
    }
}

try {
    $dsNam = uc22_danhSachNam();
    if ($namInput === '' && $maDanhSach === '' && $dsNam) $namInput = (string)$dsNam[0];
    if ($namInput !== '' && (!ctype_digit($namInput) || !in_array($namInput, array_map('strval', $dsNam), true))) {
        throw new InvalidArgumentException('Năm tuyển sinh không hợp lệ.');
    }
    $danhSach = uc22_danhSach($namInput === '' ? 0 : (int)$namInput);
    if ($maDanhSach !== '') {
        $chiTiet = uc22_chiTiet($maDanhSach);
        if ($chiTiet === null) throw new InvalidArgumentException('Không tìm thấy danh sách kết quả tuyển sinh.');
        $dieuKien = uc22_kiemTra($chiTiet);
    }
} catch (InvalidArgumentException $e) {
    $loi = $e->getMessage();
} catch (Throwable $e) {
    error_log('UC22 GET: ' . $e->getMessage());
    $loi = 'Không thể tải dữ liệu công bố. Vui lòng kiểm tra kết nối MySQL.';
}

ui_page_start('publication', 'Công bố kết quả tuyển sinh', 'HỘI ĐỒNG TUYỂN SINH',
    'Kiểm tra kết quả xét tuyển đã phê duyệt và công bố để thí sinh tra cứu.', 'UC22');
?>
<link rel="stylesheet" href="<?= e(url('assets/css/uc22-publication.css')) ?>">
<div class="uc22-page">
    <?php ui_flashes(); ?>
    <?php if ($loi !== ''): ?>
        <div class="uc22-alert uc22-alert-error" role="alert"><?= e($loi) ?></div>
    <?php endif; ?>

    <section class="uc22-card">
        <h2>Bộ lọc danh sách công bố</h2>
        <p>Chọn năm tuyển sinh để xem các danh sách đã lập.</p>
        <form method="get" action="<?= e(url('publication/tuyen-sinh.php')) ?>" class="uc22-filter">
            <div class="uc22-field">
                <label for="nam">Năm tuyển sinh</label>
                <select name="nam" id="nam">
                    <option value="">Tất cả các năm</option>
                    <?php foreach ($dsNam as $nam): ?>
                        <option value="<?= e((string)$nam) ?>" <?= $namInput === (string)$nam ? 'selected' : '' ?>>
                            <?= e((string)$nam) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="uc22-btn uc22-btn-primary" type="submit">Tra cứu</button>
            <a class="uc22-btn uc22-btn-muted" href="<?= e(url('publication/tuyen-sinh.php')) ?>">Làm mới</a>
        </form>
    </section>

    <section class="uc22-card">
        <h2>Danh sách kết quả tuyển sinh</h2>
        <p>Tổng số: <strong><?= e((string)count($danhSach)) ?></strong> danh sách.</p>
        <div class="uc22-table-scroll">
            <table class="uc22-table">
                <thead><tr><th>STT</th><th>Mã danh sách</th><th>Năm</th><th>Đợt tuyển sinh</th><th>Số kết quả</th><th>Phê duyệt</th><th>Công bố</th><th>Thao tác</th></tr></thead>
                <tbody>
                <?php if (!$danhSach): ?>
                    <tr><td colspan="8" class="uc22-empty">Chưa có danh sách phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($danhSach as $i => $row): ?>
                        <tr>
                            <td><?= e((string)($i + 1)) ?></td>
                            <td><strong><?= e($row['maDanhSach']) ?></strong></td>
                            <td><?= e((string)$row['namTuyenSinh']) ?></td>
                            <td><?= e($row['tenDot']) ?></td>
                            <td><?= e(number_format((int)$row['tongKetQua'], 0, ',', '.')) ?></td>
                            <td><span class="uc22-badge <?= $row['trangThaiPheDuyet'] === 'Đã phê duyệt' ? 'uc22-good' : 'uc22-wait' ?>"><?= e($row['trangThaiPheDuyet']) ?></span></td>
                            <td><span class="uc22-badge <?= $row['trangThaiCongBo'] === 'Đã công bố' ? 'uc22-good' : 'uc22-wait' ?>"><?= e($row['trangThaiCongBo']) ?></span></td>
                            <td><a class="uc22-btn uc22-btn-small" href="<?= e(url('publication/tuyen-sinh.php', ['nam' => $namInput, 'maDanhSach' => $row['maDanhSach']])) ?>#uc22-detail">Xem chi tiết</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($chiTiet !== null): ?>
        <section class="uc22-card" id="uc22-detail">
            <div class="uc22-head">
                <div>
                    <h2>Chi tiết danh sách <?= e($chiTiet['maDanhSach']) ?></h2>
                    <p><?= e($chiTiet['tenDot']) ?> · Năm <?= e((string)$chiTiet['namTuyenSinh']) ?></p>
                </div>
                <a class="uc22-btn uc22-btn-muted" href="<?= e(url('publication/tuyen-sinh.php', ['nam' => $namInput])) ?>">Đóng chi tiết</a>
            </div>
            <div class="uc22-stats">
                <div><span>Tổng kết quả</span><strong><?= $chiTiet['tong'] ?></strong></div>
                <div><span>Trúng tuyển</span><strong><?= $chiTiet['trungTuyen'] ?></strong></div>
                <div><span>Chưa xét</span><strong><?= $chiTiet['chuaXet'] ?></strong></div>
                <div><span>Trạng thái công bố</span><strong class="uc22-stat-text"><?= e($chiTiet['trangThaiCongBo']) ?></strong></div>
            </div>

            <?php if ($dieuKien): ?>
                <div class="uc22-alert uc22-alert-warning">
                    <strong>Chưa đủ điều kiện công bố:</strong>
                    <ul><?php foreach ($dieuKien as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
                </div>
            <?php elseif (!$xacNhan): ?>
                <a class="uc22-btn uc22-btn-primary" href="<?= e(url('publication/tuyen-sinh.php', ['nam' => $namInput, 'maDanhSach' => $maDanhSach, 'xacNhan' => 1])) ?>#uc22-detail">Công bố kết quả</a>
            <?php else: ?>
                <div class="uc22-confirm">
                    <h3>Xác nhận công bố</h3>
                    <p>Bạn có chắc muốn công bố <strong><?= e($chiTiet['maDanhSach']) ?></strong> (<?= $chiTiet['tong'] ?> kết quả)? Sau công bố, dữ liệu sẽ ở trạng thái công khai theo quyền tra cứu của hệ thống.</p>
                    <form method="post" class="uc22-actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="maDanhSach" value="<?= e($maDanhSach) ?>">
                        <button class="uc22-btn uc22-btn-primary" name="action" value="cong_bo" type="submit">Xác nhận công bố</button>
                        <a class="uc22-btn uc22-btn-muted" href="<?= e(url('publication/tuyen-sinh.php', ['maDanhSach' => $maDanhSach, 'nam' => $namInput])) ?>#uc22-detail">Hủy bỏ</a>
                    </form>
                </div>
            <?php endif; ?>

            <h3>Thí sinh trong danh sách</h3>
            <div class="uc22-table-scroll">
                <table class="uc22-table">
                    <thead><tr><th>STT</th><th>Mã thí sinh</th><th>Họ và tên</th><th>SBD</th><th>Trường THPT</th><th>Điểm xét tuyển</th><th>Kết quả</th></tr></thead>
                    <tbody>
                    <?php if (!$chiTiet['ketQua']): ?>
                        <tr><td colspan="7" class="uc22-empty">Danh sách chưa có thí sinh.</td></tr>
                    <?php else: ?>
                        <?php foreach ($chiTiet['ketQua'] as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($row['maThiSinh']) ?></td>
                                <td><?= e($row['hoTen']) ?></td>
                                <td><?= e($row['soBaoDanh'] ?? '—') ?></td>
                                <td><?= e($row['tenTruongTHPT'] ?? '—') ?></td>
                                <td><?= e(
    $row['diemXetTuyen'] === null
        ? '—'
        : number_format((float)$row['diemXetTuyen'], 2)) ?></td>
                                <td><?= e($row['trangThaiXetTuyen']) ?></td>
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

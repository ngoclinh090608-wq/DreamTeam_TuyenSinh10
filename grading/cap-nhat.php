<?php
declare(strict_types=1);
/**
 * UC13 – Cập nhật kết quả chấm thi (Actor: Hội đồng chấm thi).
 * Luồng: nhập mã phách → xem điểm hiện tại → nhập điểm mới + lý do → kiểm tra → xác nhận → lưu (1 transaction).
 */
require_once dirname(__DIR__) . '/includes/grading_ui.php';

$soPhach = grading_chuan_hoa_phach(ui_input('soPhach'));
$diemMoi = ui_input('diemMoi');
$lyDo = ui_input('lyDo');
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';

$ketQua = null; $loi = null; $chiTietLoi = []; $loiHeThong = null; $buocXacNhan = false;

if ($action !== '' && !csrf_verify()) {
    $loiHeThong = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
    $action = '';
}

if ($action === 'luu') {
    try {
        $r = capNhatDiem($soPhach, $diemMoi, $lyDo);
        flash_set('success', sprintf('Cập nhật kết quả chấm thi thành công. Điểm của bài %s đổi từ %s thành %s, lịch sử chỉnh sửa đã được ghi nhận.',
            $r['soPhach'], grading_fmt_diem($r['diemTruoc']), grading_fmt_diem($r['diemSau'])));
        ui_redirect('grading/cap-nhat.php', ['soPhach' => $r['soPhach']]);
    } catch (GradingException $e) {
        $loi = $e->getMessage(); $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC13 luu: ' . $e->getMessage());
        $loiHeThong = 'Không thể lưu kết quả chấm thi. Kết quả chấm thi trước khi chỉnh sửa được giữ nguyên.';
    }
}

if ($soPhach !== '' || isset($_GET['traCuu'])) {
    try {
        $ketQua = timBaiThiTheoMaPhach($soPhach);
        if ($action === 'kiem_tra') {
            [, $chiTietLoi] = kiemTraKetQuaCapNhat($ketQua, $diemMoi, $lyDo);
            if ($chiTietLoi) $loi = 'Kết quả chấm thi không hợp lệ.';
            else $buocXacNhan = true;
        }
    } catch (GradingException $e) {
        $ketQua = null; $loi = $e->getMessage(); $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC13: ' . $e->getMessage());
        $ketQua = null; $loiHeThong = 'Không thể truy xuất dữ liệu kết quả chấm thi.';
    }
}

ui_page_start('grading', 'Cập nhật kết quả chấm thi', 'Chấm thi · Sửa điểm', 'Nhập mã phách để xem điểm hiện tại, sau đó nhập điểm mới và lý do chỉnh sửa. Mỗi lần sửa đều được ghi vào lịch sử.', 'UC13');
grading_tabs('grading', 'cap-nhat.php');
ui_flashes();
if (isset($_GET['huy'])) ui_notice('info', 'Đã hủy cập nhật. Kết quả chấm thi không thay đổi.');
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Tìm bài thi theo mã phách</h2><p>Mã phách in trên bài thi đã rọc phách.</p></div></div>
  <form method="get" class="ui-search ui-search-one" novalidate>
    <input type="hidden" name="traCuu" value="1">
    <div class="field"><label for="soPhach">Mã phách <span>*</span></label><input id="soPhach" name="soPhach" value="<?= e($soPhach) ?>" placeholder="Ví dụ: P0052" maxlength="20" autocomplete="off"></div>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">⌕ Tìm bài thi</button></div>
  </form>
</section>

<?php if ($loi && !$ketQua) ui_notice('error', $loi, $chiTietLoi); ?>

<?php if ($ketQua): ?>
<div class="dash-panels ui-two">
<section class="panel">
  <div class="panel-heading"><div><h2>Kết quả chấm hiện tại</h2><p>Mã phách <span class="code-tag"><?= e($ketQua['soPhach']) ?></span></p></div><?= ui_status_badge($ketQua['trangThaiCongBo']) ?></div>
  <dl class="ui-kv ui-kv-1">
    <div><dt>Kỳ thi</dt><dd><?= e($ketQua['tenKyThi']) ?></dd></div>
    <div><dt>Môn thi</dt><dd><?= e($ketQua['tenMonThi']) ?> (thang điểm <?= e(grading_fmt_diem($ketQua['thangDiem'])) ?>)</dd></div>
    <div><dt>Điểm hiện tại</dt><dd class="ui-score"><?= e(grading_fmt_diem($ketQua['diemThi'])) ?> <small>/ <?= e(grading_fmt_diem($ketQua['thangDiem'])) ?></small></dd></div>
    <div><dt>Người nhập · thời điểm</dt><dd><?= e($ketQua['nguoiNhap']) ?> · <?= e(ui_fmt_time($ketQua['thoiDiemNhap'])) ?></dd></div>
  </dl>
  <a class="btn btn-outline btn-sm" href="<?= e(url('grading/lich-su.php') . '?' . http_build_query(['traCuu' => 1, 'kyThi' => $ketQua['maKyThi'], 'monThi' => $ketQua['maMonThi'], 'soPhach' => $ketQua['soPhach']])) ?>">Xem lịch sử chỉnh sửa →</a>
</section>

<section class="panel">
<?php if ($buocXacNhan): ?>
  <div class="panel-heading"><div><h2>Xác nhận cập nhật</h2><p>Kiểm tra lại thông tin trước khi lưu.</p></div></div>
  <div class="ui-confirm">
    <p>Đổi điểm bài <strong><?= e($ketQua['soPhach']) ?></strong> môn <strong><?= e($ketQua['tenMonThi']) ?></strong>:</p>
    <p class="ui-change"><span><?= e(grading_fmt_diem($ketQua['diemThi'])) ?></span> → <strong><?= e(grading_fmt_diem(str_replace(',', '.', $diemMoi))) ?></strong></p>
    <p><strong>Lý do:</strong> <?= e($lyDo) ?></p>
    <p class="muted">Người thực hiện: <?= e(GRADING_NGUOI_THUC_HIEN) ?></p>
  </div>
  <form method="post" class="ui-actions">
    <?= csrf_field() ?>
    <input type="hidden" name="soPhach" value="<?= e($ketQua['soPhach']) ?>">
    <input type="hidden" name="diemMoi" value="<?= e($diemMoi) ?>">
    <input type="hidden" name="lyDo" value="<?= e($lyDo) ?>">
    <button class="btn btn-primary" type="submit" name="action" value="luu">✓ Xác nhận cập nhật</button>
    <button class="btn btn-outline" type="submit" name="action" value="sua">Sửa lại</button>
    <a class="btn btn-outline ui-btn-cancel" href="<?= e(url('grading/cap-nhat.php') . '?huy=1') ?>">Hủy bỏ</a>
  </form>
<?php else: ?>
  <div class="panel-heading"><div><h2>Nhập kết quả mới</h2><p>Điểm từ 0 đến <?= e(grading_fmt_diem($ketQua['thangDiem'])) ?>, tối đa 2 chữ số thập phân.</p></div></div>
  <?php if ($loi) ui_notice('error', $loi, $chiTietLoi); ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="soPhach" value="<?= e($ketQua['soPhach']) ?>">
    <div class="field"><label for="diemMoi">Điểm mới <span>*</span></label><input id="diemMoi" name="diemMoi" value="<?= e($diemMoi) ?>" inputmode="decimal" placeholder="Ví dụ: 7.25" maxlength="6" autocomplete="off"></div>
    <div class="field"><label for="lyDo">Lý do chỉnh sửa <span>*</span></label><textarea id="lyDo" name="lyDo" rows="3" maxlength="255" placeholder="Ví dụ: Cộng sót điểm câu 3"><?= e($lyDo) ?></textarea></div>
    <div class="ui-actions">
      <button class="btn btn-primary" type="submit" name="action" value="kiem_tra">Tiếp tục →</button>
      <a class="btn btn-outline ui-btn-cancel" href="<?= e(url('grading/cap-nhat.php') . '?huy=1') ?>">Hủy bỏ</a>
    </div>
  </form>
<?php endif; ?>
</section>
</div>
<?php endif; ?>
<?php ui_page_end();

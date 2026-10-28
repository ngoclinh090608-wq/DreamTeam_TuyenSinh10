<?php
declare(strict_types=1);
/**
 * UC13 – Cập nhật kết quả chấm thi (Actor: Hội đồng chấm thi).
 * Luồng (báo cáo giữa kỳ): nhập mã phách → xem điểm hiện tại → nhập điểm mới + lý do → kiểm tra
 * → “Xác nhận cập nhật” hoặc “Hủy bỏ” → lưu điểm + ghi lịch sử (1 transaction).
 */
require_once dirname(__DIR__) . '/includes/grading_ui.php';
$nguoiDung = require_role(['HD_CHAM_THI']);   // UC13 – Hội đồng chấm thi

$soPhach = grading_chuan_hoa_phach(ui_input('soPhach'));
$diemMoi = ui_input('diemMoi');
$lyDo = ui_input('lyDo');
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';

$ketQua = null; $loiTraCuu = null; $loiNhap = []; $loiHeThong = ui_csrf_error(); $buocXacNhan = false;
if ($loiHeThong) $action = '';

if ($action === 'luu') {
    try {
        $r = capNhatDiem($soPhach, $diemMoi, $lyDo, $nguoiDung['hoTen']);   // ghi nhận người thực hiện
        flash_set('success', TB_CAP_NHAT_THANH_CONG);
        flash_set('info', sprintf('Điểm thi sau khi cập nhật của bài %s: %s (trước khi sửa: %s).', $r['soPhach'], grading_fmt_diem($r['diemSau']), grading_fmt_diem($r['diemTruoc'])));
        ui_redirect('grading/cap-nhat.php', ['soPhach' => $r['soPhach']]);
    } catch (GradingException $e) {
        // Lỗi kiểm tra: details là các cặp [câu thông báo, gợi ý]; lỗi lưu (11.1, 12.1): chỉ có message.
        $loiNhap = $e->details() ?: [[$e->getMessage(), null]];
    }
}

if ($soPhach !== '' || isset($_GET['traCuu'])) {
    try {
        $ketQua = timBaiThiTheoMaPhach($soPhach);
        if ($action === 'kiem_tra') {
            [, $loiNhap] = kiemTraKetQuaCapNhat($ketQua, $diemMoi, $lyDo);
            $buocXacNhan = !$loiNhap;
        }
    } catch (GradingException $e) {
        $ketQua = null; $loiTraCuu = $e->getMessage();
    } catch (Throwable $e) {
        error_log('UC13: ' . $e->getMessage());
        $ketQua = null; $loiHeThong = 'Không thể truy xuất dữ liệu kết quả chấm thi. Vui lòng thử lại sau.';
    }
}

ui_page_start('grading', 'Cập nhật kết quả chấm thi', 'Chấm thi · Sửa điểm', 'Nhập mã phách để xem điểm hiện tại, sau đó nhập điểm mới và lý do chỉnh sửa. Mỗi lần sửa đều được ghi vào lịch sử.', 'UC13');
grading_tabs('grading', 'cap-nhat.php');
ui_flashes();
if (isset($_GET['huy'])) ui_notice('info', 'Đã hủy cập nhật. Điểm thi được giữ nguyên và không tạo lịch sử chỉnh sửa.');
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Tìm bài thi theo mã phách</h2><p>Mã phách in trên bài thi đã rọc phách, ví dụ P0052.</p></div></div>
  <form method="get" class="ui-search ui-search-one" novalidate>
    <input type="hidden" name="traCuu" value="1">
    <div class="field"><label for="soPhach">Mã phách <span>*</span></label><input id="soPhach" name="soPhach" value="<?= e($soPhach) ?>" placeholder="Ví dụ: P0052" maxlength="20" autocomplete="off"></div>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">⌕ Tìm bài thi</button></div>
  </form>
</section>

<?php if ($loiTraCuu) ui_notice($loiTraCuu === TB_PHACH_KHONG_HOP_LE ? 'warning' : 'error', $loiTraCuu); ?>

<?php if ($ketQua): ?>
<div class="dash-panels ui-two">
<section class="panel">
  <div class="panel-heading"><div><h2>Kết quả chấm thi</h2><p><?= e($ketQua['tenKyThi']) ?></p></div><?= ui_status_badge($ketQua['trangThaiCongBo']) ?></div>
  <dl class="ui-kv ui-kv-1">
    <div><dt>Mã phách</dt><dd><span class="code-tag"><?= e($ketQua['soPhach']) ?></span></dd></div>
    <div><dt>Môn thi</dt><dd><?= e($ketQua['tenMonThi']) ?></dd></div>
    <div><dt>Điểm thi hiện tại</dt><dd class="ui-score"><?= e(grading_fmt_diem($ketQua['diemThi'])) ?> <small>/ <?= e(grading_fmt_diem($ketQua['thangDiem'])) ?></small></dd></div>
    <div><dt>Người nhập · thời điểm</dt><dd><?= e($ketQua['nguoiNhap']) ?> · <?= e(ui_fmt_time($ketQua['thoiDiemNhap'])) ?></dd></div>
  </dl>
  <a class="btn btn-outline btn-sm" href="<?= e(url('grading/lich-su.php') . '?' . http_build_query(['traCuu' => 1, 'kyThi' => $ketQua['maKyThi'], 'monThi' => $ketQua['maMonThi'], 'soPhach' => $ketQua['soPhach'], 'xemLichSu' => 1])) ?>#lich-su">Xem lịch sử chỉnh sửa →</a>
</section>

<section class="panel">
<?php if ($buocXacNhan): ?>
  <div class="panel-heading"><div><h2>Kiểm tra lại thông tin</h2><p>Chọn “Xác nhận cập nhật” để lưu hoặc “Hủy bỏ”.</p></div></div>
  <div class="ui-confirm">
    <p>Đổi điểm bài <strong><?= e($ketQua['soPhach']) ?></strong> môn <strong><?= e($ketQua['tenMonThi']) ?></strong>:</p>
    <p class="ui-change"><span><?= e(grading_fmt_diem($ketQua['diemThi'])) ?></span> → <strong><?= e(grading_fmt_diem(str_replace(',', '.', $diemMoi))) ?></strong></p>
    <p><strong>Lý do:</strong> <?= e($lyDo) ?></p>
    <p class="muted">Người chỉnh sửa: <?= e($nguoiDung['hoTen']) ?></p>
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
  <div class="panel-heading"><div><h2>Nhập điểm mới</h2><p>Điểm từ 0 đến <?= e(grading_fmt_gon($ketQua['thangDiem'])) ?>, bước nhảy <?= e(grading_fmt_gon(GRADING_BUOC_DIEM)) ?> điểm.</p></div></div>
  <?php foreach ($loiNhap as [$tb, $goiY]) ui_notice('error', $tb, $goiY ? [$goiY] : []); ?>
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

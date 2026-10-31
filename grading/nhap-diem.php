<?php
declare(strict_types=1);
/**
 * UC05 – Nhập điểm thi (Actor: Hội đồng chấm thi).
 * Quy trình: Chọn kỳ thi, môn thi -> Danh sách bài thi theo mã phách -> Nhập điểm -> Kiểm tra -> Lưu.
 * Bảo mật: Giữ kín thông tin nhận diện thí sinh, chỉ hiển thị số phách / mã bài thi.
 */
require_once dirname(__DIR__) . '/includes/grading_ui.php';
$nguoiDung = require_role(['HD_CHAM_THI']); // UC05 - Hội đồng chấm thi

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$maBaiThi = ui_input('baiThi');
$timKiem = trim((string)($_GET['timKiem'] ?? ''));

$dsKyThi = grading_ds_ky_thi();
$dsMonThi = grading_ds_mon();

if ($maKyThi === '' && !empty($dsKyThi)) {
    $maKyThi = (string)$dsKyThi[0]['maKyThi'];
}
if ($maMonThi === '' && !empty($dsMonThi)) {
    $maMonThi = (string)$dsMonThi[0]['maMonThi'];
}

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
$loi = null;
$chiTietLoi = [];
$loiHeThong = ui_csrf_error();
$baiChon = null;

if ($action === 'nhap_diem' && !$loiHeThong) {
    $postMaBai = ui_input('maBaiThi');
    $rawDiem = trim((string)($_POST['diemThi'] ?? ''));

    if ($rawDiem === '' || !is_numeric($rawDiem)) {
        $loi = TB_DIEM_NHAP_KHONG_HOP_LE;
        $chiTietLoi = ['Vui lòng nhập điểm số hợp lệ từ 0 đến 10 với bước nhảy 0.25.'];
        $maBaiThi = $postMaBai;
    } else {
        $diem = (float)$rawDiem;
        try {
            $nguoiNhap = (string)($nguoiDung['hoTen'] ?? $nguoiDung['tenDangNhap'] ?? GRADING_NGUOI_THUC_HIEN);
            $kq = nhapDiemThi($postMaBai, $diem, $nguoiNhap);
            flash_set('success', TB_NHAP_DIEM_THANH_CONG . " (Mã phách: {$kq['soPhach']}, Điểm: " . number_format($kq['diemThi'], 2) . ")");
            ui_redirect('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi]);
        } catch (BusinessException $e) {
            $loi = $e->getMessage();
            $chiTietLoi = $e->details();
            $maBaiThi = $postMaBai;
        } catch (Throwable $e) {
            error_log('UC05 nhap_diem: ' . $e->getMessage());
            $loi = TB_LOI_LUU_DIEM;
            $maBaiThi = $postMaBai;
        }
    }
}

$dsBaiThi = [];
$soBaiChuaCham = 0;
$soBaiDaCham = 0;

if ($maKyThi !== '' && $maMonThi !== '') {
    try {
        $dsBaiThi = timBaiThiCanNhapDiem($maKyThi, $maMonThi, $timKiem !== '' ? $timKiem : null);
        foreach ($dsBaiThi as $b) {
            if ($b['trangThaiCham'] === 'Chưa chấm' || $b['maKetQuaChamThi'] === null) {
                $soBaiChuaCham++;
            } else {
                $soBaiDaCham++;
            }
        }
    } catch (BusinessException $e) {
        $loi = $loi ?? $e->getMessage();
    } catch (Throwable $e) {
        error_log('UC05 timBaiThi: ' . $e->getMessage());
        $loiHeThong = 'Không thể tải danh sách bài thi. Vui lòng thử lại sau.';
    }
}

if ($maBaiThi !== '') {
    try {
        $baiChon = layThongTinBaiThiNhapDiem($maBaiThi);
    } catch (BusinessException $e) {
        $loi = $loi ?? $e->getMessage();
    } catch (Throwable $e) {
        error_log('UC05 layThongTin: ' . $e->getMessage());
    }
}

ui_page_start('grading', 'Nhập điểm thi', 'Chấm thi · Nhập điểm theo phách', 'Nhập điểm cho bài thi theo mã phách ẩn danh. Đảm bảo bảo mật thông tin thí sinh và tính chính xác của điểm số.', 'UC05');
grading_tabs('grading', 'nhap-diem.php');

if ($loiHeThong) ui_notice('error', $loiHeThong);
if ($loi) ui_notice('error', $loi, $chiTietLoi);
?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>Chọn phạm vi nhập điểm</h2>
      <p>Chọn Kỳ thi và Môn thi để hiển thị danh sách bài thi cần nhập điểm.</p>
    </div>
  </div>
  <form method="get" class="ui-search" novalidate>
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMonThi, 'maMonThi', 'tenMonThi', $maMonThi, '-- Chọn môn thi --'); ?>
    <div class="field">
      <label for="timKiem">Tìm theo mã phách / bài thi</label>
      <input id="timKiem" name="timKiem" value="<?= e($timKiem) ?>" placeholder="Ví dụ: P0030 hoặc BT046" maxlength="20">
    </div>
    <div class="field ui-search-btn">
      <button class="btn btn-primary" type="submit">⌕ Lọc bài thi</button>
    </div>
  </form>
</section>

<?php if ($baiChon): ?>
<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>Biểu mẫu nhập điểm</h2>
      <p>Nhập điểm số cho bài thi mang mã phách <strong><?= e($baiChon['soPhach']) ?></strong>.</p>
    </div>
  </div>
  <div class="ui-kpi-grid" style="margin-bottom: 1.5rem;">
    <div class="ui-kpi"><div class="ui-kpi-label">Mã phách</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['soPhach']) ?></div></div>
    <div class="ui-kpi"><div class="ui-kpi-label">Mã bài thi</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['maBaiThi']) ?></div></div>
    <div class="ui-kpi"><div class="ui-kpi-label">Môn thi</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['tenMonThi']) ?> (Thang <?= e($baiChon['thangDiem']) ?>)</div></div>
    <div class="ui-kpi"><div class="ui-kpi-label">Trạng thái</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['trangThaiCham']) ?></div></div>
  </div>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="nhap_diem">
    <input type="hidden" name="maBaiThi" value="<?= e($baiChon['maBaiThi']) ?>">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
    <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">

    <div class="ui-form-grid">
      <div class="field">
        <label for="diemThi">Điểm chấm thi <span>*</span></label>
        <input id="diemThi" name="diemThi" type="number" step="0.25" min="0" max="<?= e((string)$baiChon['thangDiem']) ?>"
               value="<?= e(isset($_POST['diemThi']) ? (string)$_POST['diemThi'] : ($baiChon['diemThi'] !== null ? number_format((float)$baiChon['diemThi'], 2) : '')) ?>"
               placeholder="Từ 0 đến <?= e((string)$baiChon['thangDiem']) ?>, bước nhảy 0.25 (vd: 7.50)" required autofocus>
      </div>
    </div>

    <div class="ui-actions">
      <button class="btn btn-primary" type="submit">✓ Xác nhận nhập điểm</button>
      <a class="btn btn-outline ui-btn-cancel" href="<?= e(url('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi])) ?>">Hủy bỏ</a>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>Danh sách bài thi môn <?= e($maMonThi) ?></h2>
      <p>Tổng cộng: <strong><?= count($dsBaiThi) ?></strong> bài | Chờ chấm: <span class="badge badge-warning"><?= $soBaiChuaCham ?> bài</span> | Đã chấm: <span class="badge badge-success"><?= $soBaiDaCham ?> bài</span></p>
    </div>
  </div>

  <?php if (empty($dsBaiThi)): ?>
    <div class="ui-notice ui-notice-info">
      <div><strong><?= e(TB_KHONG_CO_BAI_THI) ?></strong></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>STT</th>
            <th>Mã phách</th>
            <th>Mã bài thi</th>
            <th>Lô bài thi</th>
            <th>Điểm thi</th>
            <th>Trạng thái chấm</th>
            <th>Công bố</th>
            <th class="ui-align-right">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php $idx = 1; foreach ($dsBaiThi as $b):
            $chuaCham = ($b['trangThaiCham'] === 'Chưa chấm' || $b['maKetQuaChamThi'] === null);
          ?>
            <tr<?= ($baiChon && $baiChon['maBaiThi'] === $b['maBaiThi']) ? ' style="background:#f0f9ff"' : '' ?>>
              <td><?= $idx++ ?></td>
              <td><strong><?= e($b['soPhach']) ?></strong></td>
              <td><code><?= e($b['maBaiThi']) ?></code></td>
              <td><?= e($b['maLoBaiThi'] ?: '—') ?></td>
              <td>
                <?php if ($b['diemThi'] !== null): ?>
                  <strong><?= number_format((float)$b['diemThi'], 2) ?></strong>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($chuaCham): ?>
                  <span class="badge badge-warning">Chưa chấm</span>
                <?php else: ?>
                  <span class="badge badge-success">Đã chấm</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-neutral"><?= e($b['trangThaiCongBo'] ?: 'Chưa công bố') ?></span>
              </td>
              <td class="ui-align-right">
                <?php if ($chuaCham): ?>
                  <a class="btn btn-sm btn-primary" href="<?= e(url('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi, 'baiThi' => $b['maBaiThi']])) ?>">✍ Nhập điểm</a>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline" href="<?= e(url('grading/cap-nhat.php', ['phach' => $b['soPhach']])) ?>">✎ Sửa (UC13)</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php ui_page_end(); ?>


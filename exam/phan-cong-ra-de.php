<?php
declare(strict_types=1);
/**
 * UC08 – Xem phân công ra đề thi (Actor: Hội đồng ra đề thi).
 * Quy trình: Chọn kỳ thi, môn thi -> Hiển thị danh sách cán bộ, môn thi, thời gian và nhiệm vụ ra đề.
 * Bảo mật: Chỉ đọc, phân quyền cho Hội đồng ra đề và Ban Tuyển sinh.
 */
require_once dirname(__DIR__) . '/includes/exam_ui.php';
$nguoiDung = require_role(['HD_RA_DE', 'BAN_TUYEN_SINH', 'QUAN_TRI']); // UC08 - Hội đồng ra đề thi

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');

$dsKyThi = exam_ds_ky_thi();
$dsMonThi = exam_ds_mon_thi();

if ($maKyThi === '' && !empty($dsKyThi)) {
    $maKyThi = (string)$dsKyThi[0]['maKyThi'];
}

$loi = null;
$chiTietLoi = [];
$dsPhanCong = [];

// Nếu tài khoản là cán bộ thuộc Hội đồng ra đề (có maCanBo), lấy mã cán bộ để đối chiếu
$maCanBoDangNhap = (string)($nguoiDung['maCanBo'] ?? '');

if ($maKyThi !== '') {
    try {
        $dsPhanCong = layDanhSachPhanCongRaDe($maKyThi, null, $maMonThi !== '' ? $maMonThi : null);
    } catch (BusinessException $e) {
        $loi = $e->getMessage();
        $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC08 layDanhSachPhanCongRaDe: ' . $e->getMessage());
        $loi = EXAM_TB_LOI_TAI_PHAN_CONG;
    }
}

// Thông tin kỳ thi hiện tại
$kyThiHienTai = null;
foreach ($dsKyThi as $k) {
    if ($k['maKyThi'] === $maKyThi) {
        $kyThiHienTai = $k;
        break;
    }
}

ui_page_start('exam', 'Phân công ra đề thi', 'Tổ chức thi · Hội đồng ra đề thi', 'Tra cứu thông tin phân công nhiệm vụ ra đề thi cho từng môn thi, cán bộ phụ trách và tiến độ thực hiện.', 'UC08');
exam_tabs('phan-cong-ra-de.php');

if ($loi) ui_notice('error', $loi, $chiTietLoi);
?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>1. Chọn kỳ thi và môn thi</h2>
      <p>Chọn Kỳ thi cần xem thông tin phân công nhiệm vụ ra đề.</p>
    </div>
  </div>
  <form method="get" class="ui-search" novalidate>
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMonThi, 'maMonThi', 'tenMonThi', $maMonThi, '-- Tất cả môn thi --'); ?>
    <div class="field ui-search-btn">
      <button class="btn btn-primary" type="submit">⌕ Tra cứu</button>
    </div>
  </form>
</section>

<?php if ($kyThiHienTai): ?>
<div class="ui-kpi-grid" style="margin-bottom: 1.5rem;">
  <div class="ui-kpi">
    <div class="ui-kpi-label">Kỳ thi</div>
    <div class="ui-kpi-val" style="font-size:1.3rem"><?= e($kyThiHienTai['tenKyThi']) ?></div>
    <div class="ui-kpi-hint">Mã kỳ thi: <code><?= e($kyThiHienTai['maKyThi']) ?></code></div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Tổng số môn phân công</div>
    <div class="ui-kpi-val" style="font-size:1.3rem"><?= count($dsPhanCong) ?> nhiệm vụ</div>
    <div class="ui-kpi-hint">Thời gian kết thúc kỳ thi: <?= date('d/m/Y', strtotime((string)$kyThiHienTai['thoiGianKetThuc'])) ?></div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Cán bộ đang đăng nhập</div>
    <div class="ui-kpi-val" style="font-size:1.3rem"><?= e($nguoiDung['hoTen']) ?></div>
    <div class="ui-kpi-hint">Vai trò: <?= e(ten_vai_tro($nguoiDung['vaiTro'])) ?></div>
  </div>
</div>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>2. Danh sách phân công ra đề thi</h2>
      <p>Chi tiết các cán bộ được giao nhiệm vụ soạn thảo, phản biện đề thi theo môn.</p>
    </div>
  </div>

  <?php if (empty($dsPhanCong)): ?>
    <div class="ui-notice ui-notice-info">
      <div><strong><?= e(EXAM_TB_CHUA_CO_PHAN_CONG_RA_DE) ?></strong></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>STT</th>
            <th>Mã phân công</th>
            <th>Môn thi</th>
            <th>Thang điểm</th>
            <th>Cán bộ phụ trách</th>
            <th>Đơn vị công tác</th>
            <th>Thời gian thực hiện</th>
            <th>Nhiệm vụ cụ thể</th>
          </tr>
        </thead>
        <tbody>
          <?php $idx = 1; foreach ($dsPhanCong as $pc):
            $laNhiemVuCuaMinh = ($maCanBoDangNhap !== '' && $pc['maCanBo'] === $maCanBoDangNhap);
          ?>
            <tr<?= $laNhiemVuCuaMinh ? ' style="background:#fefce8; font-weight:500;"' : '' ?>>
              <td><?= $idx++ ?></td>
              <td><code><?= e($pc['maPhanCong']) ?></code></td>
              <td><strong><?= e($pc['tenMonThi']) ?></strong></td>
              <td>Thang <?= e($pc['thangDiem']) ?></td>
              <td>
                <strong><?= e($pc['tenCanBo']) ?></strong>
                <?php if ($laNhiemVuCuaMinh): ?>
                  <span class="badge badge-success" style="margin-left:4px;">Nhiệm vụ của bạn</span>
                <?php endif; ?>
                <br><small class="text-muted"><?= e($pc['chucVu'] ?: 'Cán bộ') ?> (<code><?= e($pc['maCanBo']) ?></code>)</small>
              </td>
              <td><?= e($pc['donViCongTac'] ?: '—') ?></td>
              <td>
                <?php if (!empty($pc['thoiGianThucHien'])): ?>
                  <?= date('d/m/Y H:i', strtotime((string)$pc['thoiGianThucHien'])) ?>
                <?php else: ?>
                  <span class="badge badge-warning">Đang thực hiện</span>
                <?php endif; ?>
              </td>
              <td><?= e($pc['thongTinNhiemVu']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php ui_page_end(); ?>


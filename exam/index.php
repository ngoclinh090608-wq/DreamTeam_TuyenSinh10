<?php
declare(strict_types=1);
/**
 * Module Kỳ thi & Phòng thi (UC06, UC07, UC08).
 * Quản lý danh mục phòng thi, phân phòng thí sinh và phân công nhiệm vụ ra đề.
 */
require_once dirname(__DIR__) . '/includes/exam_ui.php';
$nguoiDung = require_role(['BAN_TUYEN_SINH', 'HD_RA_DE', 'HD_COI_THI', 'QUAN_TRI']);

$maDiemThi = ui_input('diemThi');
$dsKyThi = exam_ds_ky_thi();
$dsDiemThi = exam_ds_diem_thi();
$dsPhong = exam_ds_phong_thi($maDiemThi);

$tongPhong = count($dsPhong);
$tongSucChua = array_sum(array_column($dsPhong, 'sucChua'));
$tongThiSinhDaPhan = array_sum(array_column($dsPhong, 'soLuongThiSinh'));

ui_page_start('exam', 'Kỳ thi & phòng thi', 'Tổ chức thi · Phòng thi · Phân phòng', 'Quản lý thông tin phòng thi, phân bổ thí sinh vào các điểm thi - phòng thi và xem phân công ra đề thi.', 'UC06 · UC07 · UC08');
exam_tabs('index.php');
?>

<div class="ui-kpi-grid">
  <div class="ui-kpi">
    <div class="ui-kpi-label">Tổng số phòng thi</div>
    <div class="ui-kpi-val"><?= $tongPhong ?></div>
    <div class="ui-kpi-hint">Tại <?= count($dsDiemThi) ?> điểm thi</div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Tổng sức chứa</div>
    <div class="ui-kpi-val"><?= $tongSucChua ?></div>
    <div class="ui-kpi-hint">Chỗ ngồi phục vụ thi</div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Thí sinh đã xếp phòng</div>
    <div class="ui-kpi-val"><?= $tongThiSinhDaPhan ?></div>
    <div class="ui-kpi-hint"><?= $tongSucChua > 0 ? round(($tongThiSinhDaPhan / $tongSucChua) * 100, 1) : 0 ?>% tỷ lệ lấp đầy</div>
  </div>
</div>

<div class="ui-cards" style="margin-bottom: 2rem;">
  <?php if (has_role($nguoiDung, ['BAN_TUYEN_SINH', 'QUAN_TRI'])): ?>
    <a class="panel" href="<?= e(url('exam/tao-phong.php')) ?>">
      <span class="quick-icon">⊞</span>
      <strong>Tạo thông tin phòng thi</strong>
      <p>Chọn kỳ thi, điểm thi và thêm phòng thi mới với sức chứa quy định.</p>
      <span class="uc-tag">UC06</span>
    </a>
    <a class="panel" href="<?= e(url('exam/phan-phong.php')) ?>">
      <span class="quick-icon">⇶</span>
      <strong>Phân phòng thi cho thí sinh</strong>
      <p>Phân bổ thí sinh theo trường THCS vào các phòng thi phù hợp, không vượt sức chứa.</p>
      <span class="uc-tag">UC07</span>
    </a>
  <?php endif; ?>

  <?php if (has_role($nguoiDung, ['HD_RA_DE', 'BAN_TUYEN_SINH', 'QUAN_TRI'])): ?>
    <a class="panel" href="<?= e(url('exam/phan-cong-ra-de.php')) ?>">
      <span class="quick-icon">📋</span>
      <strong>Xem phân công ra đề thi</strong>
      <p>Tra cứu danh sách môn thi, cán bộ phụ trách và nhiệm vụ ra đề thi.</p>
      <span class="uc-tag">UC08</span>
    </a>
  <?php endif; ?>

  <?php if (has_role($nguoiDung, ['HD_COI_THI', 'QUAN_TRI'])): ?>
    <a class="panel" href="<?= e(url('assignments/')) ?>">
      <span class="quick-icon">⇆</span>
      <strong>Phân công coi thi</strong>
      <p>Phân công 2 giám thị cho từng phòng thi và từng môn thi.</p>
      <span class="uc-tag">UC11</span>
    </a>
  <?php endif; ?>
</div>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>Danh sách phòng thi</h2>
      <p>Tình trạng bố trí phòng thi và thí sinh hiện tại.</p>
    </div>
    <?php if (has_role($nguoiDung, ['BAN_TUYEN_SINH', 'QUAN_TRI'])): ?>
      <div class="panel-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('exam/tao-phong.php')) ?>">+ Tạo phòng thi mới</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('exam/phan-phong.php')) ?>">⇶ Phân phòng thi</a>
      </div>
    <?php endif; ?>
  </div>

  <form method="get" class="ui-search" novalidate style="margin-bottom: 1.5rem;">
    <?php ui_select('diemThi', 'Lọc theo Điểm thi', $dsDiemThi, 'maDiemThi', 'tenDiemThi', $maDiemThi, '-- Tất cả điểm thi --'); ?>
    <div class="field ui-search-btn">
      <button class="btn btn-primary" type="submit">⌕ Lọc</button>
      <?php if ($maDiemThi !== ''): ?>
        <a class="btn btn-outline" href="<?= e(url('exam/')) ?>">Tất cả</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($dsPhong)): ?>
    <div class="ui-notice ui-notice-info">
      <div><strong>Không tìm thấy phòng thi nào phù hợp.</strong></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>STT</th>
            <th>Mã phòng</th>
            <th>Tên phòng thi</th>
            <th>Điểm thi</th>
            <th>Sức chứa</th>
            <th>Thí sinh đã phân</th>
            <th>Tình trạng</th>
            <?php if (has_role($nguoiDung, ['BAN_TUYEN_SINH', 'QUAN_TRI'])): ?>
              <th class="ui-align-right">Thao tác</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php $stt = 1; foreach ($dsPhong as $p):
            $sl = (int)$p['soLuongThiSinh'];
            $sc = (int)$p['sucChua'];
            $daDay = $sl >= $sc;
          ?>
            <tr>
              <td><?= $stt++ ?></td>
              <td><code><?= e($p['maPhong']) ?></code></td>
              <td><strong><?= e($p['tenPhong']) ?></strong></td>
              <td><?= e($p['tenDiemThi']) ?></td>
              <td><?= $sc ?> chỗ</td>
              <td>
                <strong><?= $sl ?></strong> / <?= $sc ?>
              </td>
              <td>
                <?php if ($sl === 0): ?>
                  <span class="badge badge-neutral">Chưa xếp phòng</span>
                <?php elseif ($daDay): ?>
                  <span class="badge badge-error">Đã đầy</span>
                <?php else: ?>
                  <span class="badge badge-success">Sẵn sàng (còn <?= $sc - $sl ?> chỗ)</span>
                <?php endif; ?>
              </td>
              <?php if (has_role($nguoiDung, ['BAN_TUYEN_SINH', 'QUAN_TRI'])): ?>
                <td class="ui-align-right">
                  <a class="btn btn-sm btn-outline" href="<?= e(url('exam/phan-phong.php', ['kyThi' => $p['maKyThi'], 'diemThi' => $p['maDiemThi'], 'phong' => $p['maPhong']])) ?>">Thí sinh</a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php ui_page_end(); ?>

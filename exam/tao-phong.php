<?php
declare(strict_types=1);
/**
 * UC06 – Tạo thông tin phòng thi (Actor: Ban Tuyển sinh Sở GD&ĐT).
 * Luồng: Chọn kỳ thi, điểm thi -> Hiển thị phòng hiện có -> Nhập mã phòng, tên phòng, sức chứa -> Kiểm tra -> Lưu.
 */
require_once dirname(__DIR__) . '/includes/exam_ui.php';
$nguoiDung = require_role(['BAN_TUYEN_SINH', 'QUAN_TRI']); // UC06 - Ban Tuyển sinh

$maKyThi = ui_input('kyThi');
$maDiemThi = ui_input('diemThi');

$dsKyThi = exam_ds_ky_thi();
$dsDiemThi = exam_ds_diem_thi($maKyThi, true);

if ($maKyThi === '' && !empty($dsKyThi)) {
    $maKyThi = (string)$dsKyThi[0]['maKyThi'];
    $dsDiemThi = exam_ds_diem_thi($maKyThi, true);
}
if ($maDiemThi === '' && !empty($dsDiemThi)) {
    $maDiemThi = (string)$dsDiemThi[0]['maDiemThi'];
}

$diemTaoPhong = ui_input('diemTaoPhong') ?: $maDiemThi;
$luaChonDiemTao = exam_ds_diem_tao_phong($maKyThi);

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
$loi = null;
$chiTietLoi = [];
$loiHeThong = ui_csrf_error();
if ($action === 'tao_diem_nv1' && !$loiHeThong) {
    try {
        $chuanBi = exam_tao_diem_thi_theo_nv1($maKyThi, ui_input('diemTaoPhong'));
        flash_set('success', "Đã tạo {$chuanBi['phongThi']} phòng mới cho điểm thi đã chọn, mỗi phòng 30 thí sinh.");
        ui_redirect('exam/tao-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $chuanBi['maDiemThi']]);
    } catch (BusinessException $e) { $loi = $e->getMessage(); $chiTietLoi = $e->details(); }
    catch (Throwable $e) { error_log('UC06/07 NV1 sites: ' . $e->getMessage()); $loi = 'Không tạo được điểm thi theo NV1. Vui lòng thử lại.'; }
}


$formMaPhong = trim((string)($_POST['maPhong'] ?? ''));
$formTenPhong = trim((string)($_POST['tenPhong'] ?? ''));
$formSucChua = trim((string)($_POST['sucChua'] ?? '30'));

if ($action === 'tao_phong' && !$loiHeThong) {
    $postDiemThi = ui_input('maDiemThi');
    $sucChua = filter_var($formSucChua, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    if ($sucChua === false) $sucChua = 0;

    try {
        $moi = luuPhongThi($postDiemThi, $formMaPhong, $formTenPhong, $sucChua);
        flash_set('success', EXAM_TB_PHONG_THANH_CONG . " (Phòng: {$moi['tenPhong']} - Mã: {$moi['maPhong']}, Sức chứa: {$moi['sucChua']})");
        ui_redirect('exam/tao-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $postDiemThi]);
    } catch (BusinessException $e) {
        $loi = $e->getMessage();
        $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC06 tao_phong: ' . $e->getMessage());
        $loi = EXAM_TB_LOI_LUU_PHONG;
    }
} elseif ($action === 'xoa_phong' && !$loiHeThong) {
    $xoaMaPhong = ui_input('maPhongXoa');
    try {
        xoaPhongThi($xoaMaPhong);
        flash_set('success', EXAM_TB_XOA_PHONG_THANH_CONG . " (Mã phòng: {$xoaMaPhong})");
        ui_redirect('exam/tao-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi]);
    } catch (BusinessException $e) {
        $loi = $e->getMessage();
        $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC06 xoa_phong: ' . $e->getMessage());
        $loi = 'Không thể xóa phòng thi. Vui lòng thử lại sau.';
    }
}

// Thông tin điểm thi đang chọn và danh sách phòng hiện có
$diemThiHienTai = null;
foreach ($dsDiemThi as $dt) {
    if ($dt['maDiemThi'] === $maDiemThi) {
        $diemThiHienTai = $dt;
        break;
    }
}

$dsPhongHienCo = [];
$goiYMaPhong = '';
if ($maDiemThi !== '') {
    $dsPhongHienCo = exam_ds_phong_thi($maDiemThi);
    $goiYMaPhong = sinhMaPhongTuDong($maDiemThi);
    if ($formMaPhong === '') {
        $formMaPhong = $goiYMaPhong;
    }
    if ($formTenPhong === '' && preg_match('/(\d+)$/', $goiYMaPhong, $m)) {
        $formTenPhong = 'Phòng ' . $m[1];
    }
}

ui_page_start('exam', 'Tạo thông tin phòng thi', 'Tổ chức thi · Quản lý phòng thi', 'Tạo mới thông tin phòng thi cho từng điểm thi, thiết lập sức chứa tối đa và kiểm tra trùng lặp.', 'UC06');
exam_tabs('tao-phong.php');
ui_flashes();

if ($loiHeThong) ui_notice('error', $loiHeThong);
if ($loi) ui_notice('error', $loi, $chiTietLoi);
?>



<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>1. Chọn Điểm thi cần tạo phòng</h2>
      <p>Chọn kỳ thi và trường THPT nhận nguyện vọng 1. Phòng thi được tạo trong điểm thi của trường đã chọn.</p>
    </div>
  </div>
  <form method="get" class="ui-search" novalidate>
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('diemThi', 'Trường THPT đăng ký NV1 / điểm thi', $dsDiemThi, 'maDiemThi', 'tenTruongVaDiemThi', $maDiemThi, '-- Chọn điểm thi --'); ?>
    <div class="field ui-search-btn">
      <button class="btn btn-primary" type="submit">Chọn điểm thi</button>
    </div>
  </form>
</section>
<section class="panel">
  <h2>Tạo phòng cho điểm thi</h2>
  <p>Chọn một điểm thi để tạo đủ phòng theo số thí sinh đăng ký NV1, mỗi phòng mới có 30 chỗ. Hệ thống chỉ bổ sung phòng tại điểm đã chọn.</p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tao_diem_nv1">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
    <?php ui_select('diemTaoPhong', 'Điểm thi cần tạo phòng', $luaChonDiemTao, 'giaTri', 'nhan', $diemTaoPhong, '-- Chọn điểm thi --'); ?>
    <div class="ui-actions"><button class="btn btn-primary" type="submit">Tạo phòng cho điểm thi đã chọn</button></div>
  </form>
</section>


<?php if ($diemThiHienTai): ?>
<div class="ui-kpi-grid" style="margin-bottom: 1.5rem;">
  <div class="ui-kpi">
    <div class="ui-kpi-label">Điểm thi</div>
    <div class="ui-kpi-val" style="font-size:1.4rem"><?= e(catalog_display_name((string)$diemThiHienTai['tenDiemThi'])) ?></div>
    <div class="ui-kpi-hint">Kỳ thi: <?= e(catalog_display_name((string)$diemThiHienTai['tenKyThi'])) ?></div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Số phòng hiện có</div>
    <div class="ui-kpi-val" style="font-size:1.4rem"><?= count($dsPhongHienCo) ?> phòng</div>
    <div class="ui-kpi-hint">Tổng sức chứa: <?= array_sum(array_column($dsPhongHienCo, 'sucChua')) ?> chỗ</div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Đơn vị đặt điểm thi</div>
    <div class="ui-kpi-val" style="font-size:1.4rem"><?= e(catalog_display_name($diemThiHienTai['tenTruongDatDiem'] ?: '—')) ?></div>
    <div class="ui-kpi-hint">Khu vực: <?= e($diemThiHienTai['maKhuVuc'] ?: 'Chưa phân vùng') ?></div>
  </div>
</div>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>2. Nhập thông tin phòng thi mới</h2>
      <p>Điền mã phòng, tên phòng và số lượng chỗ ngồi (sức chứa).</p>
    </div>
  </div>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tao_phong">
    <input type="hidden" name="maDiemThi" value="<?= e($maDiemThi) ?>">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">

    <div class="ui-form-grid">
      <div class="field">
        <label for="maPhong">Mã phòng thi <span>*</span></label>
        <input id="maPhong" name="maPhong" value="<?= e($formMaPhong) ?>" maxlength="20" placeholder="Ví dụ: P103" required>
        <small class="field-hint">Mã định danh duy nhất trong toàn hệ thống (gợi ý tự động: <code><?= e($goiYMaPhong) ?></code>).</small>
      </div>
      <div class="field">
        <label for="tenPhong">Tên phòng thi <span>*</span></label>
        <input id="tenPhong" name="tenPhong" value="<?= e($formTenPhong) ?>" maxlength="100" placeholder="Ví dụ: Phòng 103" required>
        <small class="field-hint">Tên hiển thị tại điểm thi (không được trùng tại cùng điểm thi).</small>
      </div>
      <div class="field">
        <label for="sucChua">Sức chứa tối đa (số thí sinh) <span>*</span></label>
        <input id="sucChua" name="sucChua" type="number" min="1" max="100" value="<?= e($formSucChua) ?>" placeholder="Ví dụ: 30" required>
        <small class="field-hint">Số lượng thí sinh tối đa có thể xếp vào phòng.</small>
      </div>
    </div>

    <div class="ui-actions">
      <button class="btn btn-primary" type="submit">✓ Tạo phòng thi</button>
      <a class="btn btn-outline ui-btn-cancel" href="<?= e(url('exam/tao-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi])) ?>">Làm lại</a>
    </div>
  </form>
</section>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>3. Danh sách phòng thi tại <?= e(catalog_display_name((string)$diemThiHienTai['tenDiemThi'])) ?></h2>
      <p>Các phòng thi sẵn sàng cho công tác phân phòng thi (UC07).</p>
    </div>
  </div>

  <?php if (empty($dsPhongHienCo)): ?>
    <div class="ui-notice ui-notice-info">
      <div><strong>Điểm thi này chưa có phòng thi nào. Vui lòng tạo phòng thi ở biểu mẫu trên.</strong></div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>STT</th>
            <th>Mã phòng</th>
            <th>Tên phòng thi</th>
            <th>Sức chứa</th>
            <th>Thí sinh đã phân</th>
            <th>Tình trạng</th>
            <th class="ui-align-right">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php $stt = 1; foreach ($dsPhongHienCo as $p):
            $sl = (int)$p['soLuongThiSinh'];
            $sc = (int)$p['sucChua'];
            $coThiSinh = $sl > 0;
          ?>
            <tr>
              <td><?= $stt++ ?></td>
              <td><code><?= e($p['maPhong']) ?></code></td>
              <td><strong><?= e(catalog_display_name((string)$p['tenPhong'])) ?></strong></td>
              <td><?= $sc ?> chỗ</td>
              <td><?= $sl ?> / <?= $sc ?></td>
              <td>
                <?php if ($sl === 0): ?>
                  <span class="badge badge-neutral">Trống</span>
                <?php elseif ($sl >= $sc): ?>
                  <span class="badge badge-error">Đầy</span>
                <?php else: ?>
                  <span class="badge badge-success">Còn <?= $sc - $sl ?> chỗ</span>
                <?php endif; ?>
              </td>
              <td class="ui-align-right">
                <?php if (!$coThiSinh): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng thi <?= e($p['maPhong']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="xoa_phong">
                    <input type="hidden" name="maPhongXoa" value="<?= e($p['maPhong']) ?>">
                    <input type="hidden" name="diemThi" value="<?= e($maDiemThi) ?>">
                    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
                    <button class="btn btn-sm btn-outline text-error" type="submit">Xóa</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-neutral" title="Không thể xóa khi đã có thí sinh">Đang dùng</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php ui_page_end(); ?>


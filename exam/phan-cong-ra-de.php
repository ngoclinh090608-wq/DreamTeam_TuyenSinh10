<?php
declare(strict_types=1);
/**
 * UC08 – Xem phân công ra đề thi (Actor: Hội đồng ra đề thi).
 * Luồng: Danh sách kỳ thi có phân công -> chọn kỳ thi (kiểm tra quyền) -> danh sách môn được phân công
 *        -> chọn môn -> chi tiết phân công (kỳ thi, môn thi, thời gian thực hiện, nhiệm vụ) -> quay lại danh sách.
 * Chỉ đọc: không thay đổi dữ liệu phân công.
 */
require_once dirname(__DIR__) . '/includes/exam_ui.php';
$nguoiDung = require_role(['HD_RA_DE', 'BAN_TUYEN_SINH', 'QUAN_TRI']);

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$maCanBoDangNhap = (string)($nguoiDung['maCanBo'] ?? '');

$loi = null;
$chiTietLoi = [];
$loiTai = null;
$dsKyThi = [];
$dsMon = [];
$dsChiTiet = [];
$kyThiHienTai = null;

try {
    $phamVi = layPhamViKyThiRaDe($nguoiDung);
    $dsKyThi = timKyThiDuocPhanCong($phamVi);
    if ($maKyThi === '' && $dsKyThi) $maKyThi = (string)$dsKyThi[0]['maKyThi'];

    if ($maKyThi !== '') {
        kiemTraQuyenXemPhanCong($phamVi, $maKyThi);                 // 4.1
        foreach ($dsKyThi as $k) if ($k['maKyThi'] === $maKyThi) $kyThiHienTai = $k;
        $dsMon = nhomPhanCongTheoMon(layDanhSachPhanCongRaDe($maKyThi));
        if ($maMonThi !== '') {
            try {
                $dsChiTiet = layDanhSachPhanCongRaDe($maKyThi, null, $maMonThi);
                if (!$dsChiTiet) throw new ExamException(EXAM_TB_CHUA_CO_PHAN_CONG_RA_DE, ['Môn thi đã chọn không có phân công ra đề trong kỳ thi này.']);
            } catch (BusinessException $e) {
                throw $e;
            } catch (Throwable $e) {
                error_log('UC08 chi tiet: ' . $e->getMessage());
                $loiTai = EXAM_TB_LOI_TAI_PHAN_CONG;                 // 7.1: quay lại bước 6
                $maMonThi = '';
            }
        }
    }
} catch (BusinessException $e) {
    $loi = $e->getMessage();
    $chiTietLoi = $e->details();
    if ($loi === EXAM_TB_KHONG_DU_QUYEN) { $dsMon = []; $kyThiHienTai = null; }
    $dsChiTiet = [];
} catch (Throwable $e) {
    error_log('UC08: ' . $e->getMessage());
    $loi = EXAM_TB_LOI_TAI_PHAN_CONG;
}

ui_page_start('exam', 'Phân công ra đề thi', 'Tổ chức thi · Hội đồng ra đề thi', 'Xem thông tin phân công ra đề thi theo kỳ thi và môn thi thuộc phạm vi được phân công. Dữ liệu chỉ để xem, không thay đổi.', 'UC08');
exam_tabs('phan-cong-ra-de.php');
ui_flashes();

if ($loi) ui_notice('error', $loi, $chiTietLoi);
if ($loiTai) ui_notice('error', $loiTai, ['Dữ liệu phân công không bị thay đổi. Vui lòng chọn lại môn thi.']);
?>

<?php if (!$dsKyThi && !$loi): ?>
  <?php ui_notice('info', EXAM_TB_CHUA_CO_PHAN_CONG_RA_DE, ['Không có dữ liệu nào bị thay đổi.']); ?>
<?php else: ?>
<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>1. Chọn kỳ thi</h2>
      <p>Danh sách các kỳ thi có phân công ra đề thuộc phạm vi của bạn.</p>
    </div>
  </div>
  <form method="get" class="ui-search" novalidate>
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <div class="field ui-search-btn">
      <button class="btn btn-primary" type="submit">⌕ Xem phân công</button>
    </div>
  </form>
</section>
<?php endif; ?>

<?php if ($kyThiHienTai): ?>
<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>2. Môn thi được phân công ra đề – <?= e(catalog_display_name((string)$kyThiHienTai['tenKyThi'])) ?></h2>
      <p><?= count($dsMon) ?> môn thi · <?= (int)$kyThiHienTai['soPhanCong'] ?> nhiệm vụ ra đề. Chọn môn thi để xem chi tiết.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>STT</th><th>Môn thi</th><th>Thang điểm</th><th>Cán bộ được phân công</th><th>Số nhiệm vụ</th><th class="ui-align-right">Thao tác</th></tr></thead>
      <tbody>
        <?php foreach ($dsMon as $i => $m): $dangXem = $m['maMonThi'] === $maMonThi; ?>
          <tr<?= $dangXem ? ' style="background:#f0f9ff"' : '' ?>>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($m['tenMonThi']) ?></strong></td>
            <td><?= e(rtrim(rtrim((string)$m['thangDiem'], '0'), '.')) ?></td>
            <td><?= e(implode(', ', $m['canBo'])) ?></td>
            <td><?= (int)$m['soNhiemVu'] ?></td>
            <td class="ui-align-right">
              <a class="btn btn-sm <?= $dangXem ? 'btn-primary' : 'btn-outline' ?>" href="<?= e(url('exam/phan-cong-ra-de.php', ['kyThi' => $maKyThi, 'monThi' => $m['maMonThi']])) ?>">Xem chi tiết</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<?php if ($dsChiTiet): ?>
<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>3. Chi tiết phân công ra đề – môn <?= e($dsChiTiet[0]['tenMonThi']) ?></h2>
      <p>Kỳ thi: <?= e($dsChiTiet[0]['tenKyThi']) ?> · Thang điểm <?= e(rtrim(rtrim((string)$dsChiTiet[0]['thangDiem'], '0'), '.')) ?></p>
    </div>
    <div class="panel-actions">
      <a class="btn btn-outline" href="<?= e(url('exam/phan-cong-ra-de.php', ['kyThi' => $maKyThi])) ?>">← Quay lại danh sách phân công</a>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Mã phân công</th><th>Kỳ thi</th><th>Môn thi</th><th>Cán bộ phụ trách</th><th>Đơn vị công tác</th><th>Thời gian thực hiện</th><th>Nhiệm vụ ra đề</th></tr></thead>
      <tbody>
        <?php foreach ($dsChiTiet as $pc): $cuaMinh = $maCanBoDangNhap !== '' && $pc['maCanBo'] === $maCanBoDangNhap; ?>
          <tr<?= $cuaMinh ? ' style="background:#fefce8"' : '' ?>>
            <td><code><?= e($pc['maPhanCong']) ?></code></td>
            <td><?= e(catalog_display_name((string)$pc['tenKyThi'])) ?></td>
            <td><strong><?= e($pc['tenMonThi']) ?></strong></td>
            <td>
              <strong><?= e($pc['tenCanBo']) ?></strong>
              <?php if ($cuaMinh): ?><span class="badge badge-success" style="margin-left:4px;">Nhiệm vụ của bạn</span><?php endif; ?>
              <br><small class="text-muted"><?= e($pc['chucVu'] ?: 'Cán bộ') ?> (<code><?= e($pc['maCanBo']) ?></code>)</small>
            </td>
            <td><?= e($pc['donViCongTac'] ?: '—') ?></td>
            <td><?= !empty($pc['thoiGianThucHien']) ? e(date('d/m/Y H:i', strtotime((string)$pc['thoiGianThucHien']))) : '<span class="text-muted">Chưa xác định</span>' ?></td>
            <td><?= e($pc['thongTinNhiemVu']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<?php ui_page_end(); ?>

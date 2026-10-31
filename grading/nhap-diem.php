<?php
declare(strict_types=1);
/**
 * UC05 – Nhập điểm thi (Actor: Hội đồng chấm thi).
 * Quy trình: Chọn kỳ thi, môn thi -> Danh sách bài thi theo mã phách -> Nhập điểm -> Kiểm tra -> Lưu.
 * Bảo mật: Giữ kín thông tin nhận diện thí sinh, chỉ hiển thị số phách / mã bài thi.
 */
require_once dirname(__DIR__) . '/includes/grading_ui.php';
require_once dirname(__DIR__) . '/services/grading_import_service.php';
$nguoiDung = require_role(['HD_CHAM_THI']); // UC05 - Hội đồng chấm thi

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$maBaiThi = ui_input('baiThi') ?: ui_input('maBaiThi');
$timKiem = trim((string)($_GET['timKiem'] ?? ''));

$dsKyThi = grading_ds_ky_thi();
$dsMonThi = grading_ds_mon_thi();

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
$diemChoXacNhan = null;   // bước 9: điểm đã kiểm tra hợp lệ, chờ Hội đồng chấm thi xác nhận
$diemDaNhap = $action === 'nhap_lai' ? trim((string)($_POST['diemThi'] ?? '')) : '';

$importPreview = null;
$importToken = '';
if (in_array($action, ['kiem_tra_excel', 'luu_excel'], true) && !$loiHeThong) {
    try {
        if ($action === 'kiem_tra_excel') {
            unset($_SESSION['uc05_import']);
            $file = $_FILES['excel'] ?? [];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024
                || strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'xlsx') {
                throw new GradingException('Vui lòng chọn file Excel .xlsx, dung lượng tối đa 2 MB.');
            }
            $importPreview = grading_kiem_tra_import(grading_doc_excel($file['tmp_name']), $maKyThi, $maMonThi);
            if (!array_filter($importPreview, static fn($r) => $r['loi'] !== '')) {
                $importToken = bin2hex(random_bytes(24));
                $_SESSION['uc05_import'] = ['token' => $importToken, 'rows' => $importPreview, 'ky' => $maKyThi, 'mon' => $maMonThi, 'expires' => time() + 900];
            }
        } else {
            $pending = $_SESSION['uc05_import'] ?? null;
            unset($_SESSION['uc05_import']);
            if (!$pending || !hash_equals($pending['token'], (string)($_POST['importToken'] ?? '')) || $pending['expires'] < time()
                || $pending['ky'] !== $maKyThi || $pending['mon'] !== $maMonThi) {
                throw new GradingException('Phiên nhập Excel đã hết hạn. Vui lòng tải file và kiểm tra lại.');
            }
            $n = grading_luu_import($pending['rows'], $maKyThi, $maMonThi, (string)($nguoiDung['hoTen'] ?? $nguoiDung['tenDangNhap']));
            flash_set('success', "Đã nhập điểm thành công cho {$n} bài thi từ Excel.");
            ui_redirect('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi]);
        }
    } catch (BusinessException $e) {
        $loi = $e->getMessage(); $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC05 Excel: ' . $e->getMessage());
        $loi = 'Không thể nhập Excel. Không có điểm nào được lưu. Vui lòng thử lại.';
    }
}

if (($action === 'kiem_tra' || $action === 'nhap_diem') && !$loiHeThong) {
    $maBaiThi = ui_input('maBaiThi');
    $diemDaNhap = trim((string)($_POST['diemThi'] ?? ''));
    try {
        if ($action === 'kiem_tra') {
            // Bước 7–8: nhập điểm -> hệ thống kiểm tra, chưa lưu
            $diemChoXacNhan = kiemTraNhapDiem(layThongTinBaiThiNhapDiem($maBaiThi, $maKyThi, $maMonThi), $diemDaNhap);
        } else {
            // Bước 9–12: xác nhận -> lưu, ghi người nhập và thời điểm nhập
            $nguoiNhap = (string)($nguoiDung['hoTen'] ?? $nguoiDung['tenDangNhap'] ?? GRADING_NGUOI_THUC_HIEN);
            $kq = nhapDiemThi($maBaiThi, $diemDaNhap, $nguoiNhap, $maKyThi, $maMonThi);
            flash_set('success', TB_NHAP_DIEM_THANH_CONG . " (Mã phách: {$kq['soPhach']}, Điểm: " . grading_fmt_diem($kq['diemThi']) . ')');
            // 12.1: quay lại danh sách bài thi còn cần nhập điểm
            ui_redirect('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi]);
        }
    } catch (BusinessException $e) {
        $loi = $e->getMessage();
        $chiTietLoi = $e->details();
        if ($loi === TB_DIEM_DA_XAC_NHAN) $maBaiThi = '';   // 8.3: kết thúc, không mở lại biểu mẫu
    } catch (Throwable $e) {
        error_log('UC05 ' . $action . ': ' . $e->getMessage());
        $loi = TB_LOI_LUU_DIEM;
        $chiTietLoi = ['Điểm chưa được ghi nhận. Dữ liệu điểm trước đó không bị thay đổi.'];
    }
}

$dsBaiThi = [];
$soBaiChuaCham = 0;
$soBaiDaCham = 0;

if ($maKyThi !== '' && $maMonThi !== '') {
    try {
        $dsBaiThi = timBaiThiCanNhapDiem($maKyThi, $maMonThi, $timKiem !== '' ? $timKiem : null);
        foreach ($dsBaiThi as $b) {
            baiThiCanNhapDiem($b) ? $soBaiChuaCham++ : $soBaiDaCham++;
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
        $baiChon = layThongTinBaiThiNhapDiem($maBaiThi, $maKyThi, $maMonThi);
        if (!baiThiCanNhapDiem($baiChon)) {
            // Bài đã có điểm: không hiện biểu mẫu nhập điểm (8.3)
            if (!$loi) { $loi = TB_DIEM_DA_XAC_NHAN; $chiTietLoi = ['Bài thi đã có kết quả chấm thi. Muốn điều chỉnh điểm, dùng chức năng Cập nhật kết quả chấm thi (UC13).']; }
            $baiChon = null;
        }
    } catch (BusinessException $e) {
        $loi = $loi ?? $e->getMessage();
        $chiTietLoi = $chiTietLoi ?: $e->details();
    } catch (Throwable $e) {
        error_log('UC05 layThongTin: ' . $e->getMessage());
        $loiHeThong = 'Không thể tải thông tin bài thi. Vui lòng thử lại sau.';
    }
}

ui_page_start('grading', 'Nhập điểm thi', 'Chấm thi · Nhập điểm theo phách', 'Nhập điểm cho bài thi theo mã phách ẩn danh. Đảm bảo bảo mật thông tin thí sinh và tính chính xác của điểm số.', 'UC05');
grading_tabs('grading', 'nhap-diem.php');

ui_flashes();
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

<section class="panel" aria-labelledby="uc05-methods">
  <div class="panel-heading"><div>
    <h2 id="uc05-methods">Hai cách nhập điểm thi</h2>
    <p>Chọn kỳ thi và môn thi ở trên. Chỉ nhập bài chưa có điểm; điều chỉnh điểm đã lưu tại UC13.</p>
  </div></div>
  <div class="ui-actions">
    <a class="btn btn-primary" href="#nhap-tay">1. Nhập tay</a>
    <a class="btn btn-outline" href="#nhap-excel">2. Import Excel</a>
  </div>
</section>

<?php if (!$baiChon): ?>
<section class="panel" id="nhap-tay">
  <div class="panel-heading"><div><h2>1. Nhập điểm bằng tay</h2>
    <p>Nhập mã phách hoặc mã bài thi và điểm, sau đó kiểm tra trước khi xác nhận lưu.</p>
  </div></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="kiem_tra">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
    <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">
    <div class="ui-form-grid">
      <div class="field"><label for="maBaiNhapTay">Mã phách / mã bài thi <span>*</span></label>
        <input id="maBaiNhapTay" name="maBaiThi" list="baiChuaCham" placeholder="Ví dụ: P0030 hoặc BT046" maxlength="20" required>
        <datalist id="baiChuaCham"><?php foreach ($dsBaiThi as $b): if (baiThiCanNhapDiem($b)): ?>
          <option value="<?= e($b['maBaiThi']) ?>"><?= e($b['soPhach']) ?></option>
        <?php endif; endforeach; ?></datalist>
      </div>
      <div class="field"><label for="diemNhapTay">Điểm thi <span>*</span></label>
        <input id="diemNhapTay" name="diemThi" type="number" min="0" step="0.25" placeholder="Ví dụ: 7.75" required>
      </div>
    </div>
    <div class="ui-actions"><button class="btn btn-primary" type="submit">Kiểm tra điểm</button></div>
  </form>
  <?php if ($soBaiChuaCham === 0 && $timKiem === ''): ?>
    <p role="status">Không còn bài chưa chấm trong phạm vi này. Hãy chọn kỳ thi / môn thi khác để nhập điểm mới.</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($baiChon): ?>
<section class="panel" id="nhap-tay">
  <div class="panel-heading">
    <div>
      <h2>1. Nhập điểm bằng tay</h2>
      <p>Nhập điểm số cho bài thi mang mã phách <strong><?= e($baiChon['soPhach']) ?></strong>.</p>
    </div>
  </div>
  <div class="ui-kpi-grid" style="margin-bottom: 1.5rem;">
    <div class="ui-kpi"><div class="ui-kpi-label">Mã phách</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['soPhach']) ?></div></div>
    <div class="ui-kpi"><div class="ui-kpi-label">Mã bài thi</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['maBaiThi']) ?></div></div>
    <div class="ui-kpi"><div class="ui-kpi-label">Môn thi</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['tenMonThi']) ?> (Thang <?= e($baiChon['thangDiem']) ?>)</div></div>
    <div class="ui-kpi"><div class="ui-kpi-label">Trạng thái</div><div class="ui-kpi-val" style="font-size:1.5rem"><?= e($baiChon['trangThaiCham']) ?></div></div>
  </div>

  <?php if ($diemChoXacNhan !== null): ?>
    <!-- Bước 9: xác nhận nhập điểm; 9.1: nhập lại điểm trước khi lưu -->
    <div class="ui-notice ui-notice-info" role="status"><span class="ui-notice-icon" aria-hidden="true">ⓘ</span><div>
      <strong>Điểm hợp lệ. Vui lòng xác nhận nhập điểm.</strong>
      <ul><li>Mã phách <?= e($baiChon['soPhach']) ?> · <?= e($baiChon['tenMonThi']) ?>: <strong><?= e(grading_fmt_diem($diemChoXacNhan)) ?></strong> / <?= e(grading_fmt_gon($baiChon['thangDiem'])) ?> điểm</li></ul>
    </div></div>
    <div class="ui-actions">
      <form method="post" novalidate style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="nhap_diem">
        <input type="hidden" name="maBaiThi" value="<?= e($baiChon['maBaiThi']) ?>">
        <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
        <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">
        <input type="hidden" name="diemThi" value="<?= e($diemDaNhap) ?>">
        <button class="btn btn-primary" type="submit">✓ Xác nhận nhập điểm</button>
      </form>
      <form method="post" novalidate style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="nhap_lai">
        <input type="hidden" name="maBaiThi" value="<?= e($baiChon['maBaiThi']) ?>">
        <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
        <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">
        <input type="hidden" name="diemThi" value="<?= e($diemDaNhap) ?>">
        <button class="btn btn-outline" type="submit">✎ Nhập lại điểm</button>
      </form>
    </div>
  <?php else: ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="kiem_tra">
    <input type="hidden" name="maBaiThi" value="<?= e($baiChon['maBaiThi']) ?>">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
    <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">

    <div class="ui-form-grid">
      <div class="field">
        <label for="diemThi">Điểm chấm thi <span>*</span></label>
        <input id="diemThi" name="diemThi" type="number" step="0.25" min="0" max="<?= e((string)$baiChon['thangDiem']) ?>"
               value="<?= e($diemDaNhap) ?>"
               placeholder="Từ 0 đến <?= e(grading_fmt_gon($baiChon['thangDiem'])) ?>, bước nhảy 0.25 (vd: 7.50)" required autofocus>
      </div>
    </div>

    <div class="ui-actions">
      <button class="btn btn-primary" type="submit">Kiểm tra điểm</button>
      <a class="btn btn-outline ui-btn-cancel" href="<?= e(url('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi])) ?>">Hủy bỏ</a>
    </div>
  </form>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="panel" id="nhap-excel">
  <div class="panel-heading"><div><h2>2. Import điểm từ Excel</h2>
    <p>Dùng trang tính đầu tiên, cột A: MaPhach, cột B: DiemThi. Tối đa 500 bài, 2 MB; không dùng công thức. Điểm từ 0 đến thang điểm môn, bước 0.25.</p>
  </div></div>
  <p><a class="btn btn-outline" href="<?= e(url('assets/templates/mau-nhap-diem.xlsx')) ?>" download>Tải file Excel mẫu</a></p>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="kiem_tra_excel">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
    <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">
    <div class="field"><label for="excel">File điểm Excel (.xlsx)</label><input id="excel" name="excel" type="file" accept=".xlsx" required></div>
    <div class="ui-actions"><button class="btn btn-primary" type="submit">Kiểm tra file Excel</button></div>
  </form>
  <?php if ($importPreview !== null): ?>
    <h3>Kết quả kiểm tra — chưa lưu điểm</h3>
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Dòng Excel</th><th>Mã phách / bài thi</th><th>Điểm</th><th>Kết quả</th></tr></thead><tbody>
      <?php foreach ($importPreview as $row): ?>
        <tr><td><?= (int)$row['dong'] ?></td><td><?= e($row['phach'] ?? $row['ma']) ?></td><td><?= e($row['diem']) ?></td><td><?= e($row['loi'] ?: 'Hợp lệ') ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php if ($importToken !== ''): ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="luu_excel">
        <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
        <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">
        <input type="hidden" name="importToken" value="<?= e($importToken) ?>">
        <div class="ui-actions"><button class="btn btn-primary" type="submit">Xác nhận nhập <?= count($importPreview) ?> điểm từ Excel</button>
        <a class="btn btn-outline" href="<?= e(url('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi])) ?>">Hủy</a></div>
      </form>
    <?php else: ?><p role="alert">File có lỗi. Chưa lưu điểm nào; hãy sửa các dòng lỗi và tải lại file.</p><?php endif; ?>
  <?php endif; ?>
</section>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>Danh sách bài thi môn <?= e($maMonThi) ?></h2>
      <p>Tổng cộng: <strong><?= count($dsBaiThi) ?></strong> bài | Chờ chấm: <span class="badge badge-warning"><?= $soBaiChuaCham ?> bài</span> | Đã chấm: <span class="badge badge-success"><?= $soBaiDaCham ?> bài</span></p>
    </div>
  </div>

  <?php if ($maKyThi !== '' && $maMonThi !== '' && $soBaiChuaCham === 0 && $timKiem === ''): ?>
    <?php ui_notice('info', TB_KHONG_CO_BAI_THI, $dsBaiThi ? ['Tất cả bài thi của môn đã chọn đều đã có điểm.'] : []); ?>
  <?php endif; ?>
  <?php if (empty($dsBaiThi)): ?>
    <?php if ($timKiem !== '') ui_notice('info', 'Không tìm thấy bài thi phù hợp với mã phách / mã bài thi đã nhập.'); ?>
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
            $chuaCham = baiThiCanNhapDiem($b);
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


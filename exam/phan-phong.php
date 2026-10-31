<?php
declare(strict_types=1);
/**
 * UC07 – Phân phòng thi cho thí sinh (Actor: Ban Tuyển sinh Sở GD&ĐT).
 * Luồng: Chọn kỳ thi, điểm thi -> Kiểm tra điều kiện & sức chứa -> Xem trước phân phòng -> Xác nhận lưu bằng transaction.
 */
require_once dirname(__DIR__) . '/includes/exam_ui.php';
$nguoiDung = require_role(['BAN_TUYEN_SINH', 'QUAN_TRI']); // UC07 - Ban Tuyển sinh

$maKyThi = ui_input('kyThi');
$maDiemThi = ui_input('diemThi');
$maPhongLoc = ui_input('phong');

$dsKyThi = exam_ds_ky_thi();
$dsDiemThi = exam_ds_diem_thi($maKyThi);

if ($maKyThi === '' && !empty($dsKyThi)) {
    $maKyThi = (string)$dsKyThi[0]['maKyThi'];
    $dsDiemThi = exam_ds_diem_thi($maKyThi);
}
if ($maDiemThi === '' && !empty($dsDiemThi)) {
    $maDiemThi = (string)$dsDiemThi[0]['maDiemThi'];
}

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
$loi = null;
$chiTietLoi = [];
$loiHeThong = ui_csrf_error();
$preview = null;

// Xử lý các thao tác POST
if ($action !== '' && !$loiHeThong) {
    if ($action === 'xem_truoc') {
        try {
            $preview = kiemTraVaTinhToanPhanPhong($maKyThi, $maDiemThi);
        } catch (BusinessException $e) {
            $loi = $e->getMessage();
            $chiTietLoi = $e->details();
        } catch (Throwable $e) {
            error_log('UC07 xem_truoc: ' . $e->getMessage());
            $loi = 'Không thể tính toán phân phòng thi. Vui lòng kiểm tra lại dữ liệu.';
        }
    } elseif ($action === 'xac_nhan_luu') {
        $phanBoPost = is_array($_POST['phanBo'] ?? null) ? $_POST['phanBo'] : [];
        if (empty($phanBoPost)) {
            $loi = 'Dữ liệu phân phòng không hợp lệ hoặc bị trống.';
        } else {
            try {
                $kq = luuKetQuaPhanPhong($maKyThi, $maDiemThi, $phanBoPost);
                flash_set('success', EXAM_TB_PHAN_PHONG_THANH_CONG . " (Đã lưu phân phòng cho {$kq['soLuong']} thí sinh)");
                ui_redirect('exam/phan-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi]);
            } catch (BusinessException $e) {
                $loi = $e->getMessage();
                $chiTietLoi = $e->details();
            } catch (Throwable $e) {
                error_log('UC07 xac_nhan_luu: ' . $e->getMessage());
                $loi = EXAM_TB_LOI_LUU_PHAN_PHONG;
            }
        }
    } elseif ($action === 'chuyen_phong') {
        $maPP = ui_input('maPhanPhong');
        $phongMoi = ui_input('maPhongMoi');
        try {
            chuyenPhongThiSinh($maPP, $phongMoi);
            flash_set('success', EXAM_TB_CHUYEN_PHONG_THANH_CONG . " (Thí sinh đã chuyển sang phòng {$phongMoi})");
            ui_redirect('exam/phan-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi]);
        } catch (BusinessException $e) {
            $loi = $e->getMessage();
            $chiTietLoi = $e->details();
        } catch (Throwable $e) {
            error_log('UC07 chuyen_phong: ' . $e->getMessage());
            $loi = 'Không thể chuyển phòng thi cho thí sinh.';
        }
    } elseif ($action === 'huy_phan_phong') {
        $maPP = ui_input('maPhanPhong');
        try {
            huyPhanPhongThiSinh($maPP);
            flash_set('success', EXAM_TB_HUY_PHAN_PHONG_THANH_CONG);
            ui_redirect('exam/phan-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi]);
        } catch (Throwable $e) {
            error_log('UC07 huy_phan_phong: ' . $e->getMessage());
            $loi = 'Không thể hủy phân phòng thi.';
        }
    }
}

// Lấy dữ liệu điểm thi và danh sách phòng thi hiện tại
$diemThiHienTai = null;
foreach ($dsDiemThi as $dt) {
    if ($dt['maDiemThi'] === $maDiemThi) {
        $diemThiHienTai = $dt;
        break;
    }
}

$dsPhong = [];
$dsThiSinhPhuHop = [];
$dsDaPhanPhong = [];
$dsKhongPhuHop = [];

if ($maDiemThi !== '') {
    $dsPhong = exam_ds_phong_thi($maDiemThi);
    try {
        $dsThiSinhPhuHop = layDanhSachThiSinhPhuHopDiemThi($maKyThi, $maDiemThi);
        $dsKhongPhuHop = layThiSinhKhongPhuHopDiemThi($maDiemThi);
        // Lấy danh sách đã phân phòng
        $sqlDaPhan = 'SELECT pp.maPhanPhong, t.maThiSinh, t.hoTen, t.soBaoDanh, t.ngaySinh,
                             c.tenTruongTHCS, p.maPhong, p.tenPhong, pp.ngayPhan, pp.trangThai
                      FROM PhanPhongThi pp
                      JOIN ThiSinh t ON t.maThiSinh = pp.maThiSinh
                      JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
                      JOIN PhongThi p ON p.maPhong = pp.maPhong
                      WHERE p.maDiemThi = ? AND pp.trangThai = "Đã phân"';
        $paramsDaPhan = [$maDiemThi];
        if ($maPhongLoc !== '') {
            $sqlDaPhan .= ' AND p.maPhong = ?';
            $paramsDaPhan[] = $maPhongLoc;
        }
        $sqlDaPhan .= ' ORDER BY p.maPhong, ' . exam_thu_tu_ten_abc_sql();
        $st = db()->prepare($sqlDaPhan);
        $st->execute($paramsDaPhan);
        $dsDaPhanPhong = $st->fetchAll();
    } catch (BusinessException $e) {
        $loi = $loi ?? $e->getMessage();
    } catch (Throwable $e) {
        error_log('UC07 load: ' . $e->getMessage());
        $loiHeThong = 'Không thể tải danh sách thí sinh. Vui lòng thử lại sau.';
    }
}

$tongSucChua = array_sum(array_column($dsPhong, 'sucChua'));
$soLuongThiSinhCanPhan = count($dsThiSinhPhuHop);
$soLuongDaPhan = count(array_filter($dsThiSinhPhuHop, fn($t) => $t['maPhanPhong'] !== null));

ui_page_start('exam', 'Phân phòng thi cho thí sinh', 'Tổ chức thi · Phân bổ phòng thi', 'Tự động phân bổ thí sinh theo trường THCS vào các phòng thi tại điểm thi, đảm bảo đúng sức chứa và liên kết dữ liệu an toàn.', 'UC07');
exam_tabs('phan-phong.php');
ui_flashes();

if ($loiHeThong) ui_notice('error', $loiHeThong);
if ($loi) ui_notice('error', $loi, $chiTietLoi);
?>

<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>1. Chọn Điểm thi cần phân phòng</h2>
      <p>Chọn Kỳ thi và Điểm thi tổ chức.</p>
    </div>
  </div>
  <form method="get" class="ui-search" novalidate>
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('diemThi', 'Điểm thi', $dsDiemThi, 'maDiemThi', 'tenDiemThi', $maDiemThi, '-- Chọn điểm thi --'); ?>
    <div class="field ui-search-btn">
      <button class="btn btn-primary" type="submit">⌕ Xem thông tin</button>
    </div>
  </form>
</section>

<?php if ($diemThiHienTai): ?>
<div class="ui-kpi-grid" style="margin-bottom: 1.5rem;">
  <div class="ui-kpi">
    <div class="ui-kpi-label">Điểm thi</div>
    <div class="ui-kpi-val" style="font-size:1.3rem"><?= e($diemThiHienTai['tenDiemThi']) ?></div>
    <div class="ui-kpi-hint">Khu vực: <?= e($diemThiHienTai['maKhuVuc'] ?: 'Tất cả') ?></div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Số phòng thi</div>
    <div class="ui-kpi-val" style="font-size:1.3rem"><?= count($dsPhong) ?> phòng</div>
    <div class="ui-kpi-hint">Tổng sức chứa: <strong><?= $tongSucChua ?></strong> chỗ</div>
  </div>
  <div class="ui-kpi">
    <div class="ui-kpi-label">Thí sinh đủ điều kiện</div>
    <div class="ui-kpi-val" style="font-size:1.3rem"><?= $soLuongThiSinhCanPhan ?> thí sinh</div>
    <div class="ui-kpi-hint">Đã xếp phòng: <?= $soLuongDaPhan ?> / <?= $soLuongThiSinhCanPhan ?></div>
  </div>
</div>

<!-- Nút thực hiện phân phòng tự động -->
<section class="panel" style="margin-bottom: 1.5rem;">
  <div class="panel-heading">
    <div>
      <h2>2. Thực hiện phân phòng thi tự động</h2>
      <p>Hệ thống lấy thí sinh đủ điều kiện của các trường THCS cùng khu vực với điểm thi, xếp ABC theo tên gọi, khi trùng tên thì xét họ và tên đệm, rồi tự động lấp lần lượt từng phòng theo sức chứa. Không chia nhóm theo trường THCS.</p>
    </div>
    <div class="panel-actions">
      <form method="post" novalidate style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="xem_truoc">
        <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
        <input type="hidden" name="diemThi" value="<?= e($maDiemThi) ?>">
        <button class="btn btn-primary" type="submit">⚙ Bắt đầu phân phòng (Xem trước)</button>
      </form>
      <a class="btn btn-outline" href="<?= e(url('exam/tao-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi])) ?>">+ Thêm phòng thi (UC06)</a>
    </div>
  </div>

  <?php if (empty($dsPhong)): ?>
    <div class="ui-notice ui-notice-warning">
      <div><strong>Điểm thi này chưa có phòng thi nào.</strong> Vui lòng tạo phòng thi trước khi thực hiện phân phòng.</div>
    </div>
  <?php elseif ($tongSucChua < $soLuongThiSinhCanPhan): ?>
    <div class="ui-notice ui-notice-error">
      <div><strong>Sức chứa phòng thi không đủ.</strong> Tổng sức chứa (<?= $tongSucChua ?> chỗ) nhỏ hơn số lượng thí sinh (<?= $soLuongThiSinhCanPhan ?> thí sinh). Thiếu <?= $soLuongThiSinhCanPhan - $tongSucChua ?> chỗ.</div>
    </div>
  <?php else: ?>
    <?php if ($dsKhongPhuHop):
      ui_notice('warning', EXAM_TB_DIEM_THI_KHONG_PHU_HOP, array_merge(
        ['Các thí sinh sau đang được xếp tại điểm thi nhưng trường THCS khác khu vực; hệ thống không phân tự động các thí sinh này vào điểm thi và vẫn tính chỗ họ đang ngồi:'],
        array_map(fn($r) => "{$r['hoTen']} ({$r['maThiSinh']}) – {$r['tenTruongTHCS']}, {$r['tenKhuVuc']} – phòng {$r['maPhong']}", $dsKhongPhuHop)
      ));
    endif; ?>
    <p class="text-muted" style="margin:0.5rem 0;">Sức chứa phòng thi đảm bảo phục vụ đầy đủ cho toàn bộ thí sinh. Nhấn nút <strong>Bắt đầu phân phòng</strong> để xem trước kết quả.</p>
  <?php endif; ?>
</section>

<!-- Bảng Xem Trước (Preview) Phân Phòng Thi -->
<?php if ($preview): ?>
<section class="panel" style="margin-bottom: 1.5rem; border: 2px solid #2563eb;">
  <div class="panel-heading">
    <div>
      <h2 style="color: #2563eb;">3. Kết quả phân phòng dự kiến (Xem trước)</h2>
      <p>Kiểm tra danh sách bố trí từng phòng trước khi xác nhận lưu chính thức vào cơ sở dữ liệu.</p>
    </div>
  </div>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="xac_nhan_luu">
    <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
    <input type="hidden" name="diemThi" value="<?= e($maDiemThi) ?>">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
      <?php foreach ($preview['phanBo'] as $maPhong => $pb):
        $pInfo = $pb['phong'];
        $slXep = $pb['soLuong'];
        $sc = $pb['sucChua'];
      ?>
        <div class="panel" style="background: #f8fafc; border: 1px solid #cbd5e1;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.75rem;">
            <div>
              <strong><?= e($pInfo['tenPhong']) ?> (<code><?= e($maPhong) ?></code>)</strong>
            </div>
            <div>
              <span class="badge <?= $slXep >= $sc ? 'badge-error' : 'badge-success' ?>"><?= $slXep ?> / <?= $sc ?> chỗ</span>
            </div>
          </div>

          <div style="max-height: 250px; overflow-y: auto;">
            <table class="data-table" style="font-size: 0.85rem;">
              <thead>
                <tr>
                  <th>SBD</th>
                  <th>Họ và tên</th>
                  <th>Trường THCS</th>
                  <th>Đổi phòng</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pb['thiSinh'])): ?>
                  <tr><td colspan="4" class="text-muted ui-align-center">Chưa có thí sinh</td></tr>
                <?php else: ?>
                  <?php foreach ($pb['thiSinh'] as $ts): ?>
                    <tr>
                      <td><strong><?= e($ts['soBaoDanh'] ?: '—') ?></strong></td>
                      <td><?= e($ts['hoTen']) ?></td>
                      <td><small><?= e($ts['tenTruongTHCS']) ?></small></td>
                      <td>
                        <select name="phanBo[<?= e($ts['maThiSinh']) ?>]" style="padding:2px 4px; font-size:0.8rem;">
                          <?php foreach ($dsPhong as $otherP): ?>
                            <option value="<?= e($otherP['maPhong']) ?>"<?= $otherP['maPhong'] === $maPhong ? ' selected' : '' ?>>
                              <?= e($otherP['tenPhong']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="ui-actions">
      <button class="btn btn-primary btn-lg" type="submit">✓ Xác nhận lưu kết quả phân phòng</button>
      <a class="btn btn-outline ui-btn-cancel" href="<?= e(url('exam/phan-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi])) ?>">Hủy bỏ</a>
    </div>
  </form>
</section>
<?php endif; ?>

<!-- Danh sách thí sinh đã phân phòng chính thức -->
<section class="panel">
  <div class="panel-heading">
    <div>
      <h2>4. Danh sách thí sinh đã phân phòng tại điểm thi</h2>
      <p>Hiện có <strong><?= $soLuongDaPhan ?></strong> thí sinh đã được xếp phòng chính thức.</p>
    </div>
  </div>

  <div style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
    <a class="btn btn-sm <?= $maPhongLoc === '' ? 'btn-primary' : 'btn-outline' ?>" href="<?= e(url('exam/phan-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi])) ?>">Tất cả phòng (<?= count($dsDaPhanPhong) ?>)</a>
    <?php foreach ($dsPhong as $p): ?>
      <a class="btn btn-sm <?= $maPhongLoc === $p['maPhong'] ? 'btn-primary' : 'btn-outline' ?>" href="<?= e(url('exam/phan-phong.php', ['kyThi' => $maKyThi, 'diemThi' => $maDiemThi, 'phong' => $p['maPhong']])) ?>">
        <?= e($p['tenPhong']) ?> (<?= $p['soLuongThiSinh'] ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($dsDaPhanPhong)): ?>
    <div class="ui-notice ui-notice-info">
      <div><strong>Chưa có thí sinh nào được phân phòng tại điểm thi này.</strong> Hãy dùng chức năng phân phòng tự động ở trên.</div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>STT</th>
            <th>Số báo danh</th>
            <th>Mã thí sinh</th>
            <th>Họ và tên</th>
            <th>Ngày sinh</th>
            <th>Trường THCS</th>
            <th>Phòng thi</th>
            <th>Ngày phân</th>
            <th class="ui-align-right">Chuyển phòng / Hủy</th>
          </tr>
        </thead>
        <tbody>
          <?php $idx = 1; foreach ($dsDaPhanPhong as $r): ?>
            <tr>
              <td><?= $idx++ ?></td>
              <td><strong><?= e($r['soBaoDanh'] ?: '—') ?></strong></td>
              <td><code><?= e($r['maThiSinh']) ?></code></td>
              <td><strong><?= e($r['hoTen']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime((string)$r['ngaySinh'])) ?></td>
              <td><?= e($r['tenTruongTHCS']) ?></td>
              <td><span class="badge badge-success"><?= e($r['tenPhong']) ?> (<?= e($r['maPhong']) ?>)</span></td>
              <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime((string)$r['ngayPhan'])) ?></small></td>
              <td class="ui-align-right">
                <!-- Form chuyển phòng nhanh -->
                <form method="post" style="display:inline-flex; gap:4px; align-items:center;" onsubmit="return confirm('Chuyển thí sinh <?= e($r['hoTen']) ?> sang phòng đã chọn?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="chuyen_phong">
                  <input type="hidden" name="maPhanPhong" value="<?= e($r['maPhanPhong']) ?>">
                  <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
                  <input type="hidden" name="diemThi" value="<?= e($maDiemThi) ?>">
                  <select name="maPhongMoi" style="padding:2px 4px; font-size:0.8rem;" required>
                    <option value="">-- Chuyển sang --</option>
                    <?php foreach ($dsPhong as $p): if ($p['maPhong'] !== $r['maPhong']): ?>
                      <option value="<?= e($p['maPhong']) ?>"<?= (int)$p['soLuongThiSinh'] >= (int)$p['sucChua'] ? ' disabled' : '' ?>>
                        <?= e($p['tenPhong']) ?> (<?= $p['soLuongThiSinh'] ?>/<?= $p['sucChua'] ?>)
                      </option>
                    <?php endif; endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-outline" type="submit">Chuyển</button>
                </form>

                <form method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn hủy phân phòng của thí sinh <?= e($r['hoTen']) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="huy_phan_phong">
                  <input type="hidden" name="maPhanPhong" value="<?= e($r['maPhanPhong']) ?>">
                  <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
                  <input type="hidden" name="diemThi" value="<?= e($maDiemThi) ?>">
                  <button class="btn btn-sm btn-outline text-error" type="submit" title="Hủy phân phòng">✕</button>
                </form>
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


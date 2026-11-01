<?php
declare(strict_types=1);

/**
 * Giao diện và Bộ điều khiển phía HỌC SINH:
 * ĐĂNG KÝ TUYỂN SINH LỚP 10 VÀ QUẢN LÝ NGUYỆN VỌNG (NV1, NV2, NV3).
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/aspiration_service.php';

// Xác thực nghiêm ngặt vai trò Học sinh và lấy thông tin tài khoản từ session
$user = require_role(['HOC_SINH']);
$maThiSinh = (string)($user['maThiSinh'] ?? '');

// Xử lý gửi biểu mẫu đăng ký / cập nhật nguyện vọng (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(403);
        flash_set('error', 'Mã xác thực biểu mẫu (CSRF) không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang và thử lại.');
    } else {
        try {
            $rawWishes = $_POST['wishes'] ?? [];
            if (!is_array($rawWishes)) {
                $rawWishes = [];
            }
            $result = luuNguyenVong($user, $rawWishes);
            flash_set('success', $result['message']);
        } catch (BusinessException $e) {
            flash_set('error', $e->getMessage());
        } catch (PDOException $e) {
            error_log('aspiration_save PDO error: ' . $e->getMessage());
            flash_set('error', 'Lỗi cơ sở dữ liệu khi lưu nguyện vọng. Dữ liệu trước đó được giữ nguyên an toàn.');
        } catch (Throwable $e) {
            error_log('aspiration_save exception: ' . $e->getMessage());
            flash_set('error', 'Không thể lưu nguyện vọng lúc này. Vui lòng thử lại sau.');
        }
    }
    header('Location: ' . url('aspirations/'), true, 303);
    exit;
}

// Xử lý GET: Lấy dữ liệu để hiển thị
$thiSinh = layThongTinThiSinhDangKy($maThiSinh);
$quyDinh = layQuyDinhNguyenVong($thiSinh['namTuyenSinh'] ?? null);
$dsTruong = layDanhSachTruongTHPT($thiSinh['maDot'] ?? null);
$nguyenVongHienTai = layDanhSachNguyenVongThiSinh($maThiSinh);

// Đánh giá quyền chỉnh sửa
$canEdit = false;
$lockReason = '';

if (!$thiSinh) {
    $lockReason = TB_NV_KHONG_TIM_THAY_THI_SINH;
} elseif ($thiSinh['trangThaiDangKyDuThi'] === 'Từ chối') {
    $lockReason = TB_NV_HO_SO_TU_CHOI;
} elseif ($thiSinh['dieuKienDuThi'] !== 1) {
    $lockReason = TB_NV_CHUA_DU_DIEU_KIEN;
} elseif (!$quyDinh) {
    $lockReason = 'Chưa thiết lập quy định thời gian tuyển sinh.';
} elseif ($quyDinh['is_upcoming']) {
    $lockReason = TB_NV_CHUA_MO . ' Thời gian mở: ' . $quyDinh['start_formatted'];
} elseif ($quyDinh['is_expired']) {
    $lockReason = TB_NV_HET_HAN . ' Hạn chót đã kết thúc vào: ' . $quyDinh['end_formatted'];
} else {
    $canEdit = true;
}

$page_title = 'Đăng ký tuyển sinh lớp 10 & Nguyện vọng';
$layout = 'dashboard';
$active = 'aspirations';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="ui-page student-aspiration-page">
  <!-- Đầu trang -->
  <div class="page-head">
    <div>
      <div class="eyebrow">
        <span></span>CỔNG TUYỂN SINH VÀO LỚP 10 THPT
      </div>
      <h1>Đăng ký tuyển sinh lớp 10 & Quản lý nguyện vọng</h1>
      <p>Học sinh đăng ký tối đa 3 nguyện vọng vào các trường THPT công lập theo thứ tự ưu tiên (NV1 &gt; NV2 &gt; NV3).</p>
    </div>
    <div class="page-head-actions">
      <?php if (!empty($nguyenVongHienTai)): ?>
        <button type="button" class="btn btn-outline btn-sm student-print-button" data-student-print>
          🖨 In phiếu đăng ký
        </button>
      <?php endif; ?>
      <a href="<?= e(url('admin/')) ?>" class="btn btn-soft btn-sm">
        ♙ Hồ sơ cá nhân
      </a>
    </div>
  </div>

  <!-- Thông báo Flash -->
  <?php foreach (flash_take() as $f): ?>
    <div class="ui-notice <?= $f['type'] === 'success' ? 'ui-notice-success' : 'ui-notice-error' ?>" role="alert">
      <span class="ui-notice-icon" aria-hidden="true"><?= $f['type'] === 'success' ? '✓' : '!' ?></span>
      <div><strong><?= e($f['message']) ?></strong></div>
    </div>
  <?php endforeach; ?>

  <?php if (!$canEdit && $lockReason !== ''): ?>
    <div class="ui-notice ui-notice-info ui-mt" role="status">
      <span class="ui-notice-icon" aria-hidden="true">ℹ</span>
      <div>
        <strong>Chế độ xem thông tin:</strong> <?= e($lockReason) ?>
        <?php if ($quyDinh && $quyDinh['is_expired']): ?>
          <p class="ui-mt-xs" style="margin:4px 0 0;font-size:13px;color:var(--muted)">Dữ liệu nguyện vọng đã được chốt và chuyển sang trạng thái chỉ xem.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="student-overview ui-mt">
    <!-- PANEL 1: THÔNG TIN HỒ SƠ THÍ SINH -->
    <section class="panel">
      <div class="panel-heading">
        <div>
          <h2>Thông tin thí sinh</h2>
          <p>Dữ liệu đồng bộ từ trường THCS</p>
        </div>
        <span class="status <?= ($thiSinh['trangThaiDangKyDuThi'] ?? '') === 'Đã duyệt' ? 'status-success' : 'status-pending' ?>">
          <?= e($thiSinh['trangThaiDangKyDuThi'] ?? 'Chưa duyệt') ?>
        </span>
      </div>

      <?php if ($thiSinh): ?>
        <dl class="student-profile-fields">
          <div>
            <dt>Mã thí sinh</dt>
            <dd><span class="code-tag"><?= e($thiSinh['maThiSinh']) ?></span></dd>
          </div>
          <div>
            <dt>Mã hồ sơ</dt>
            <dd><strong><?= e($thiSinh['maHoSo']) ?></strong></dd>
          </div>
          <div class="student-field-wide">
            <dt>Họ và tên học sinh</dt>
            <dd><strong style="font-size:16px"><?= e($thiSinh['hoTen']) ?></strong></dd>
          </div>
          <div>
            <dt>Ngày sinh</dt>
            <dd><?= e($thiSinh['ngaySinhFormatted']) ?></dd>
          </div>
          <div>
            <dt>Số báo danh</dt>
            <dd><?= e($thiSinh['soBaoDanh'] ?: 'Chưa cấp') ?></dd>
          </div>
          <div class="student-field-wide">
            <dt>Trường THCS tốt nghiệp</dt>
            <dd><?= e(school_display_name($thiSinh['tenTruongTHCS'])) ?> (<?= e($thiSinh['tenKhuVuc']) ?>)</dd>
          </div>
          <div>
            <dt>Điều kiện dự thi</dt>
            <dd>
              <?php if ($thiSinh['dieuKienDuThi'] === 1): ?>
                <span class="status status-success">✓ Đủ điều kiện</span>
              <?php else: ?>
                <span class="status status-pending">Chưa đủ điều kiện</span>
              <?php endif; ?>
            </dd>
          </div>
          <div>
            <dt>Đợt tuyển sinh</dt>
            <dd><?= e($thiSinh['tenDot'] ?? 'Tuyển sinh lớp 10 năm 2026') ?></dd>
          </div>
        </dl>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
          <small class="muted">Cần sửa thông tin cá nhân, liên hệ, học bạ?</small>
          <a href="<?= e(url('admin/')) ?>" class="text-link" style="font-size:13px;font-weight:600">
            → Đến trang Cập nhật hồ sơ
          </a>
        </div>
      <?php else: ?>
        <p class="muted">Không tải được thông tin thí sinh.</p>
      <?php endif; ?>
    </section>

    <!-- PANEL 2: THỜI HẠN & QUY ĐỊNH NGUYỆN VỌNG -->
    <section class="panel">
      <div class="panel-heading">
        <div>
          <h2>Thời hạn &amp; Quy định</h2>
          <p>Quy chế tuyển sinh vào lớp 10</p>
        </div>
        <?php if ($quyDinh && $quyDinh['is_open']): ?>
          <span class="status status-success">Đang mở đăng ký</span>
        <?php elseif ($quyDinh && $quyDinh['is_expired']): ?>
          <span class="status status-pending">Đã hết hạn</span>
        <?php else: ?>
          <span class="status status-pending">Chưa mở</span>
        <?php endif; ?>
      </div>

      <div style="display:grid;gap:14px;margin-bottom:18px">
        <div style="padding:14px;background:var(--bg);border-radius:8px;border:1px solid var(--border)">
          <div style="font-size:12px;color:var(--muted);margin-bottom:4px">THỜI GIAN ĐĂNG KÝ VÀ ĐIỀU CHỈNH</div>
          <div style="font-size:14px;color:var(--ink)">
            <strong>Từ:</strong> <?= e($quyDinh['start_formatted'] ?? '—') ?>
            <br>
            <strong>Đến:</strong> <?= e($quyDinh['end_formatted'] ?? '—') ?>
          </div>
        </div>

        <div style="font-size:13px;line-height:1.7;color:var(--ink)">
          <strong>Quy tắc xét tuyển nguyện vọng:</strong>
          <ul style="margin:6px 0 0;padding-left:18px">
            <li>Mỗi thí sinh được đăng ký tối đa <strong><?= (int)($quyDinh['soLuongNguyenVongToiDa'] ?? 3) ?> nguyện vọng</strong> vào các trường THPT công lập.</li>
            <li><strong>Nguyện vọng 1 (NV1)</strong> là nguyện vọng bắt buộc và được xét ưu tiên trước NV2 và NV3.</li>
            <li><strong>Không được chọn trùng trường</strong> giữa các nguyện vọng (NV1, NV2, NV3 phải là 3 trường khác nhau).</li>
            <li>Trong thời gian đăng ký mở, học sinh được quyền thay đổi, điều chỉnh nguyện vọng mà không bị giới hạn số lần lưu.</li>
          </ul>
        </div>
      </div>
    </section>
  </div>

  <!-- PANEL 3: BIỂU MẪU ĐĂNG KÝ NGUYỆN VỌNG -->
  <section class="panel ui-mt" aria-label="Biểu mẫu đăng ký nguyện vọng">
    <div class="panel-heading">
      <div>
        <h2>Đăng ký nguyện vọng tuyển sinh</h2>
        <p><?= $canEdit ? 'Chọn trường THPT cho từng nguyện vọng theo thứ tự ưu tiên giảm dần' : 'Thông tin nguyện vọng đã ghi nhận (chế độ chỉ xem)' ?></p>
      </div>
      <?php if (!empty($nguyenVongHienTai)): ?>
        <span class="count-tag">Đã đăng ký <?= count($nguyenVongHienTai) ?> nguyện vọng</span>
      <?php else: ?>
        <span class="count-tag" style="background:#fff3cd;color:#856404">Chưa có nguyện vọng</span>
      <?php endif; ?>
    </div>

    <form method="post" id="wish-form" action="<?= e(url('aspirations/')) ?>" class="ui-mt">
      <?= csrf_field() ?>

      <div style="display:grid;gap:20px">
        <?php for ($order = 1; $order <= (int)($quyDinh['soLuongNguyenVongToiDa'] ?? 3); $order++):
            $currentWish = $nguyenVongHienTai[$order] ?? null;
            $selectedSchoolCode = $currentWish['maTruongTHPT'] ?? '';
            $isReq = ($order === 1);
        ?>
          <div class="student-wish-card" style="padding:18px;border:1px solid var(--border);border-radius:10px;background:#fff" data-wish-card="<?= $order ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
              <div style="display:flex;align-items:center;gap:10px">
                <span class="student-wish-badge" style="display:inline-grid;place-items:center;width:32px;height:32px;border-radius:6px;background:var(--soft-blue);color:var(--primary-dark);font-weight:800;font-size:14px">
                  <?= $order ?>
                </span>
                <strong style="font-size:16px;color:var(--primary-dark)">
                  Nguyện vọng <?= $order ?>
                  <?php if ($isReq): ?>
                    <span style="color:#e03131" title="Bắt buộc">* (Bắt buộc)</span>
                  <?php else: ?>
                    <span style="font-size:13px;color:var(--muted);font-weight:normal">(Tùy chọn)</span>
                  <?php endif; ?>
                </strong>
              </div>
              <?php if ($currentWish): ?>
                <span class="status status-success" style="font-size:12px">
                  Đã ghi nhận: <?= e(date('d/m/Y H:i', strtotime($currentWish['ngayDangKy']))) ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="field" style="margin-bottom:10px">
              <label for="wish-select-<?= $order ?>" style="font-size:13px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px">
                Chọn trường THPT tiếp nhận nguyện vọng <?= $order ?>:
              </label>
              <select
                id="wish-select-<?= $order ?>"
                name="wishes[<?= $order ?>]"
                class="student-wish-select"
                data-wish-order="<?= $order ?>"
                <?= $isReq ? 'required' : '' ?>
                <?= !$canEdit ? 'disabled' : '' ?>
                style="width:100%;padding:11px 13px;font-size:15px;border-radius:6px;border:1px solid var(--border);background:<?= $canEdit ? '#fff' : '#f8f9fa' ?>"
              >
                <option value=""><?= $isReq ? '-- Vui lòng chọn trường THPT cho NV1 --' : '-- Không đăng ký nguyện vọng ' . $order . ' --' ?></option>
                <?php foreach ($dsTruong as $school):
                    $isSelected = ($selectedSchoolCode === $school['maTruongTHPT']);
                    $schoolLabel = $school['maTruongTHPT'] . ' – ' . school_display_name($school['tenTruongTHPT']) . ' (' . $school['tenKhuVuc'] . ')';
                    if (!empty($school['chiTieuTuyenSinh'])) {
                        $schoolLabel .= ' · Chỉ tiêu: ' . $school['chiTieuTuyenSinh'];
                    }
                ?>
                  <option
                    value="<?= e($school['maTruongTHPT']) ?>"
                    data-school-name="<?= e(school_display_name($school['tenTruongTHPT'])) ?>"
                    data-school-region="<?= e($school['tenKhuVuc']) ?>"
                    data-school-quota="<?= e((string)($school['chiTieuTuyenSinh'] ?? '—')) ?>"
                    data-school-method="<?= e((string)($school['phuongThucTuyenSinh'] ?? 'Thi tuyển')) ?>"
                    <?= $isSelected ? 'selected' : '' ?>
                  >
                    <?= e($schoolLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Dynamic school preview info -->
            <div id="school-preview-<?= $order ?>" class="student-school-preview" style="padding:12px 14px;background:var(--bg);border-radius:6px;font-size:13px;margin-top:8px;<?= $selectedSchoolCode ? '' : 'display:none;' ?>">
              <span style="color:var(--muted)">Thông tin trường:</span>
              <strong id="preview-name-<?= $order ?>" style="color:var(--primary-dark);margin-left:6px">
                <?= $currentWish ? e(school_display_name($currentWish['tenTruongTHPT'])) : '' ?>
              </strong>
              <div style="display:flex;gap:18px;margin-top:6px;flex-wrap:wrap;color:var(--muted)">
                <span>Khu vực: <strong id="preview-region-<?= $order ?>" style="color:var(--ink)"><?= $currentWish ? e($currentWish['tenKhuVuc']) : '—' ?></strong></span>
                <span>Chỉ tiêu tuyển sinh: <strong id="preview-quota-<?= $order ?>" style="color:var(--ink)"><?= $currentWish && $currentWish['chiTieuTuyenSinh'] ? e((string)$currentWish['chiTieuTuyenSinh']) : '—' ?></strong></span>
                <span>Phương thức: <strong id="preview-method-<?= $order ?>" style="color:var(--ink)"><?= $currentWish && $currentWish['phuongThucTuyenSinh'] ? e($currentWish['phuongThucTuyenSinh']) : 'Thi tuyển' ?></strong></span>
              </div>
            </div>
          </div>
        <?php endfor; ?>
      </div>

      <!-- Action buttons -->
      <?php if ($canEdit): ?>
        <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;align-items:center;flex-wrap:wrap">
          <button type="reset" class="btn btn-outline">
            ↺ Đặt lại
          </button>
          <button type="button" class="btn btn-primary" id="btn-open-confirm">
            ✓ Xem lại &amp; Xác nhận đăng ký
          </button>
        </div>
      <?php endif; ?>
    </form>
  </section>

  <!-- PANEL 4: DANH SÁCH NGUYỆN VỌNG ĐÃ GHI NHẬN (TỔNG HỢP) -->
  <?php if (!empty($nguyenVongHienTai)): ?>
    <section class="panel table-panel ui-mt" aria-label="Danh sách nguyện vọng đã lưu">
      <div class="table-toolbar">
        <div class="table-title">
          <h2>Nguyện vọng đã lưu trong hệ thống</h2>
          <p>Dữ liệu chính thức được lưu trữ tại cơ sở dữ liệu tuyển sinh</p>
        </div>
        <div class="toolbar-right">
          <button type="button" class="btn btn-outline btn-sm student-print-button" data-student-print>
            🖨 In phiếu đăng ký nguyện vọng
          </button>
        </div>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col" style="width:60px;text-align:center">Thứ tự</th>
              <th scope="col">Mã trường</th>
              <th scope="col">Tên trường THPT</th>
              <th scope="col">Khu vực</th>
              <th scope="col">Chỉ tiêu</th>
              <th scope="col">Ngày đăng ký</th>
              <th scope="col">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($nguyenVongHienTai as $w): ?>
              <tr>
                <td style="text-align:center"><strong style="color:var(--primary-dark)">NV<?= (int)$w['thuTuNguyenVong'] ?></strong></td>
                <td><span class="code-tag"><?= e($w['maTruongTHPT']) ?></span></td>
                <td><strong><?= e(school_display_name($w['tenTruongTHPT'])) ?></strong></td>
                <td><?= e($w['tenKhuVuc']) ?></td>
                <td><?= e((string)($w['chiTieuTuyenSinh'] ?: '—')) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($w['ngayDangKy']))) ?></td>
                <td><span class="status status-success"><?= e($w['trangThaiNguyenVong']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>

<!-- HỘP THOẠI XÁC NHẬN ĐĂNG KÝ NGUYỆN VỌNG (CONFIRM DIALOG) -->
<dialog id="student-confirm-dialog" class="student-dialog" style="max-width:560px" aria-labelledby="confirm-dialog-title">
  <div class="student-dialog-heading">
    <div>
      <div class="student-dialog-overline" style="color:var(--primary)">XÁC NHẬN NGUYỆN VỌNG</div>
      <h2 id="confirm-dialog-title">Kiểm tra thông tin đăng ký</h2>
    </div>
    <button type="button" data-student-dialog-close aria-label="Đóng hộp thoại">×</button>
  </div>

  <div style="font-size:14px;color:var(--ink)">
    <p>Vui lòng kiểm tra kỹ danh sách các nguyện vọng bạn đã chọn trước khi chính thức lưu vào hệ thống:</p>
    
    <div style="padding:12px;background:var(--bg);border-radius:8px;margin-bottom:14px;border:1px solid var(--border)">
      <div>Thí sinh: <strong><?= e($thiSinh['hoTen'] ?? '') ?></strong> (Mã HS: <?= e($thiSinh['maHoSo'] ?? '') ?>)</div>
      <div>Trường THCS: <?= e(school_display_name($thiSinh['tenTruongTHCS'] ?? '')) ?></div>
    </div>

    <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:14px">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:#eaf2f8;text-align:left">
            <th style="padding:8px 12px;border-bottom:1px solid var(--border)">Thứ tự</th>
            <th style="padding:8px 12px;border-bottom:1px solid var(--border)">Trường THPT đã chọn</th>
          </tr>
        </thead>
        <tbody id="confirm-wishes-list">
          <!-- Điền bằng JS -->
        </tbody>
      </table>
    </div>

    <div style="padding:10px 12px;background:#fff8df;border:1px solid #ffeeba;border-radius:6px;font-size:12px;color:#856404;margin-bottom:16px">
      ⚠️ <strong>Cam kết:</strong> Tôi xin cam đoan các thông tin nguyện vọng đăng ký trên là hoàn toàn chính xác theo nguyện vọng của bản thân và gia đình.
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px">
      <button type="button" class="btn btn-outline" data-student-dialog-close>
        Quay lại sửa
      </button>
      <button type="button" class="btn btn-primary" id="btn-submit-confirmed">
        ✓ Xác nhận &amp; Lưu đăng ký
      </button>
    </div>
  </div>
</dialog>

<!-- PHẦN IN PHIẾU ĐĂNG KÝ NGUYỆN VỌNG (DÀNH CHO @media print) -->
<div id="print-aspiration-ticket" class="print-only" style="display:none">
  <div style="text-align:center;margin-bottom:20px">
    <div style="font-size:12pt;font-weight:bold">SỞ GIÁO DỤC VÀ ĐÀO TẠO TP. HỒ CHÍ MINH</div>
    <div style="font-size:11pt">TRƯỜNG THCS: <?= mb_strtoupper(e($thiSinh['tenTruongTHCS'] ?? '')) ?></div>
    <div style="margin-top:14px;font-size:16pt;font-weight:bold">PHIẾU ĐĂNG KÝ NGUYỆN VỌNG TUYỂN SINH VÀO LỚP 10 THPT</div>
    <div style="font-size:12pt;font-style:italic">NĂM HỌC 2026 – 2027</div>
  </div>

  <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:11pt">
    <tr>
      <td style="padding:6px 0;width:50%">Họ và tên thí sinh: <strong><?= e($thiSinh['hoTen'] ?? '') ?></strong></td>
      <td style="padding:6px 0;width:50%">Ngày sinh: <strong><?= e($thiSinh['ngaySinhFormatted'] ?? '') ?></strong></td>
    </tr>
    <tr>
      <td style="padding:6px 0">Mã thí sinh: <strong><?= e($thiSinh['maThiSinh'] ?? '') ?></strong></td>
      <td style="padding:6px 0">Mã hồ sơ: <strong><?= e($thiSinh['maHoSo'] ?? '') ?></strong></td>
    </tr>
    <tr>
      <td style="padding:6px 0">Số báo danh: <strong><?= e($thiSinh['soBaoDanh'] ?: 'Chưa cấp') ?></strong></td>
      <td style="padding:6px 0">Khu vực: <strong><?= e($thiSinh['tenKhuVuc'] ?? '') ?></strong></td>
    </tr>
  </table>

  <div style="font-weight:bold;margin-bottom:8px;font-size:12pt">DANH SÁCH NGUYỆN VỌNG ĐĂNG KÝ:</div>
  <table style="width:100%;border-collapse:collapse;margin-bottom:24px;font-size:11pt" border="1">
    <thead>
      <tr style="background:#f2f2f2;text-align:center">
        <th style="padding:8px">Thứ tự</th>
        <th style="padding:8px">Mã trường</th>
        <th style="padding:8px">Tên trường THPT</th>
        <th style="padding:8px">Khu vực</th>
        <th style="padding:8px">Chỉ tiêu</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 1; $i <= 3; $i++):
          $item = $nguyenVongHienTai[$i] ?? null;
      ?>
        <tr>
          <td style="padding:8px;text-align:center;font-weight:bold">NV<?= $i ?></td>
          <td style="padding:8px;text-align:center"><?= e($item['maTruongTHPT'] ?? '—') ?></td>
          <td style="padding:8px"><?= $item ? e(school_display_name($item['tenTruongTHPT'])) : 'Không đăng ký' ?></td>
          <td style="padding:8px;text-align:center"><?= e($item['tenKhuVuc'] ?? '—') ?></td>
          <td style="padding:8px;text-align:center"><?= e((string)($item['chiTieuTuyenSinh'] ?? '—')) ?></td>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <table style="width:100%;border-collapse:collapse;margin-top:30px;font-size:11pt;text-align:center">
    <tr>
      <td style="width:33%;vertical-align:top">
        <strong>HỌC SINH</strong><br>
        <em>(Ký và ghi rõ họ tên)</em>
        <div style="height:70px"></div>
        <?= e($thiSinh['hoTen'] ?? '') ?>
      </td>
      <td style="width:33%;vertical-align:top">
        <strong>CHA MẸ / NGƯỜI GIÁM HỘ</strong><br>
        <em>(Ký và ghi rõ họ tên)</em>
        <div style="height:70px"></div>
      </td>
      <td style="width:34%;vertical-align:top">
        <strong>XÁC NHẬN CỦA TRƯỜNG THCS</strong><br>
        <em>(Ký tên và đóng dấu)</em>
        <div style="height:70px"></div>
      </td>
    </tr>
  </table>
</div>

<style>
@media print {
  body.student-body { background:#fff !important; }
  .student-site-header, .student-portal-welcome, .student-portal-nav, .student-site-footer,
  .page-head, .ui-notice, .student-overview, #wish-form, .table-toolbar, .table-bottom,
  .student-dialog, dialog {
    display: none !important;
  }
  #print-aspiration-ticket.print-only {
    display: block !important;
    color: #000 !important;
    font-family: "Times New Roman", Times, serif !important;
    padding: 20px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const selects = document.querySelectorAll('.student-wish-select');
  const confirmDialog = document.getElementById('student-confirm-dialog');
  const btnOpenConfirm = document.getElementById('btn-open-confirm');
  const btnSubmitConfirmed = document.getElementById('btn-submit-confirmed');
  const wishForm = document.getElementById('wish-form');
  const confirmList = document.getElementById('confirm-wishes-list');

  // Cập nhật card preview khi người dùng chọn trường
  function updatePreview(select) {
    const order = select.dataset.wishOrder;
    const previewBox = document.getElementById('school-preview-' + order);
    const selectedOpt = select.options[select.selectedIndex];

    if (!selectedOpt || !selectedOpt.value) {
      if (previewBox) previewBox.style.display = 'none';
      return;
    }

    if (previewBox) {
      previewBox.style.display = 'block';
      const nameEl = document.getElementById('preview-name-' + order);
      const regEl = document.getElementById('preview-region-' + order);
      const quoEl = document.getElementById('preview-quota-' + order);
      const metEl = document.getElementById('preview-method-' + order);

      if (nameEl) nameEl.textContent = selectedOpt.dataset.schoolName || '';
      if (regEl) regEl.textContent = selectedOpt.dataset.schoolRegion || '—';
      if (quoEl) quoEl.textContent = selectedOpt.dataset.schoolQuota || '—';
      if (metEl) metEl.textContent = selectedOpt.dataset.schoolMethod || 'Thi tuyển';
    }
  }

  selects.forEach(sel => {
    sel.addEventListener('change', function () {
      updatePreview(this);
      validateDuplicates();
    });
  });

  // Kiểm tra trùng lặp trường ở frontend để cảnh báo người dùng ngay lập tức
  function validateDuplicates() {
    const chosen = [];
    let duplicateFound = false;

    selects.forEach(sel => {
      sel.setCustomValidity('');
      const val = sel.value.trim();
      if (val !== '') {
        if (chosen.includes(val)) {
          sel.setCustomValidity('Không được chọn trùng trường THPT giữa các nguyện vọng.');
          duplicateFound = true;
        } else {
          chosen.push(val);
        }
      }
    });

    return !duplicateFound;
  }

  // Mở modal xác nhận
  if (btnOpenConfirm && confirmDialog) {
    btnOpenConfirm.addEventListener('click', function () {
      // Kiểm tra hợp lệ form HTML5
      if (!wishForm.reportValidity()) {
        return;
      }
      if (!validateDuplicates()) {
        wishForm.reportValidity();
        return;
      }

      // Kiểm tra NV1 bắt buộc
      const nv1 = document.getElementById('wish-select-1');
      if (!nv1 || !nv1.value) {
        alert('Vui lòng chọn trường THPT cho Nguyện vọng 1.');
        nv1?.focus();
        return;
      }

      // Xây dựng danh sách xem lại trong modal
      confirmList.innerHTML = '';
      selects.forEach(sel => {
        const order = sel.dataset.wishOrder;
        const opt = sel.options[sel.selectedIndex];
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid var(--border)';

        const tdOrder = document.createElement('td');
        tdOrder.style.padding = '8px 12px';
        tdOrder.innerHTML = '<strong>NV' + order + '</strong>';

        const tdName = document.createElement('td');
        tdName.style.padding = '8px 12px';
        if (opt && opt.value) {
          tdName.innerHTML = '<strong>' + (opt.dataset.schoolName || opt.text) + '</strong> <span class="code-tag">' + opt.value + '</span>';
        } else {
          tdName.innerHTML = '<span style="color:var(--muted)">-- Không đăng ký --</span>';
        }

        tr.appendChild(tdOrder);
        tr.appendChild(tdName);
        confirmList.appendChild(tr);
      });

      if (typeof confirmDialog.showModal === 'function') {
        confirmDialog.showModal();
        document.body.classList.add('student-dialog-open');
      } else {
        // Fallback nếu trình duyệt cũ không hỗ trợ dialog
        if (confirm('Bạn có chắc chắn muốn lưu các nguyện vọng đã chọn?')) {
          wishForm.submit();
        }
      }
    });
  }

  // Nút submit trong modal xác nhận
  if (btnSubmitConfirmed && wishForm) {
    btnSubmitConfirmed.addEventListener('click', function () {
      btnSubmitConfirmed.disabled = true;
      btnSubmitConfirmed.textContent = 'Đang lưu dữ liệu…';
      wishForm.submit();
    });
  }
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

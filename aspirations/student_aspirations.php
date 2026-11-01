<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/aspiration_service.php';
require_once dirname(__DIR__) . '/services/csrf.php';

$account = require_module('aspirations');
$isStudent = has_role($account, ['HOC_SINH']);
$owner = (string)($account['maThiSinh'] ?? '');

// Dành cho trường hợp cán bộ/admin muốn thử nghiệm giao diện học sinh
if (!$isStudent && isset($_GET['preview_student'])) {
    $owner = trim((string)$_GET['preview_student']);
    $isStudent = true;
}

if (!$isStudent || $owner === '') {
    header('Location: ' . url('admin/'), true, 303);
    exit;
}

// 1. Kiểm tra tiền điều kiện
$candidate = aspiration_get_candidate($owner);
$regulation = aspiration_get_regulation();
$schools = aspiration_get_schools();

if (!$candidate) {
    flash_set('error', 'Không tìm thấy thông tin hồ sơ thí sinh gắn với tài khoản này.');
    header('Location: ' . url('admin/'), true, 303);
    exit;
}

// 2. Xử lý lưu / cập nhật nguyện vọng (Basic Flow 5 & 6)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Mã xác thực phiên làm việc (CSRF) không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang.');
        header('Location: ' . url('aspirations/'), true, 303);
        exit;
    }

    $rawWishes = $_POST['wishes'] ?? [];
    if (!is_array($rawWishes)) $rawWishes = [];

    // Lọc và sắp xếp lại mảng dữ liệu gửi lên
    $cleanWishes = [];
    foreach ($rawWishes as $item) {
        if (!is_array($item)) continue;
        $schId = trim((string)($item['maTruongTHPT'] ?? ''));
        if ($schId === '') continue; // Bỏ qua nếu dòng trống
        $cleanWishes[] = [
            'maTruongTHPT' => $schId,
            'lopChuyen' => trim((string)($item['lopChuyen'] ?? 'Thường')),
            'thuTuNguyenVong' => count($cleanWishes) + 1,
            'maNguyenVong' => !empty($item['maNguyenVong']) ? trim((string)$item['maNguyenVong']) : null,
        ];
    }

    try {
        if (!$regulation) {
            throw new BusinessException('Hệ thống chưa thiết lập quy chế đăng ký nguyện vọng cho năm học này.');
        }

        // Bước 4: Kiểm tra tính hợp lệ (Validation)
        $validated = aspiration_validate($owner, $cleanWishes, $regulation, $candidate);

        // Bước 6: Cập nhật CSDL
        aspiration_save($owner, $validated, $regulation['maQuyDinh']);

        flash_set('success', 'Lưu và cập nhật danh sách nguyện vọng xét tuyển lớp 10 thành công!');
    } catch (BusinessException $e) {
        // Exception Flow 4.1
        flash_set('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log('aspiration save error: ' . $e->getMessage());
        flash_set('error', 'Đã có lỗi hệ thống xảy ra khi lưu nguyện vọng: ' . $e->getMessage());
    }

    header('Location: ' . url('aspirations/'), true, 303);
    exit;
}

// 3. Tải danh sách nguyện vọng hiện có để hiển thị trang [LT1]
$currentWishes = aspiration_get_wishes($owner);
$maxWishes = (int)($regulation['soLuongNguyenVongToiDa'] ?? 3);
$isRegistrationOpen = !empty($regulation['is_open']);
$isEligibleExam = !empty($candidate['registered_exam_ok']);
$canEdit = $isRegistrationOpen && $isEligibleExam;

$page_title = 'Quản lý nguyện vọng lớp 10';
$layout = 'dashboard';
$active = 'aspirations';

require dirname(__DIR__) . '/includes/header.php';
?>

<style>
.aspiration-page {
  max-width: 1080px;
  margin: 0 auto 40px;
}
.aspiration-head {
  margin-bottom: 24px;
}
.aspiration-head h1 {
  font-size: 26px;
  font-weight: 800;
  color: var(--ink, #1f2d3d);
  margin: 4px 0 8px;
}
.aspiration-head p {
  color: var(--muted, #617387);
  font-size: 14.5px;
  margin: 0;
}
.preconditions-card {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  background: white;
  border: 1px solid var(--border, #dde5ed);
  border-radius: 10px;
  padding: 16px 20px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.precondition-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.precondition-icon {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  font-weight: 800;
  font-size: 15px;
  flex-shrink: 0;
}
.icon-ok {
  background: #e6f7ec;
  color: #1e7e34;
}
.icon-warn {
  background: #fdf3d8;
  color: #b26a00;
}
.icon-err {
  background: #fde8e8;
  color: #c81e1e;
}
.precondition-item strong {
  display: block;
  font-size: 13.5px;
  color: var(--ink, #1f2d3d);
  margin-bottom: 2px;
}
.precondition-item small {
  display: block;
  font-size: 12px;
  color: var(--muted, #617387);
  line-height: 1.4;
}
.student-brief-card {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: #f8fafc;
  border: 1px solid var(--border, #dde5ed);
  border-radius: 8px;
  padding: 14px 20px;
  margin-bottom: 24px;
}
.student-brief-card .student-id-block {
  display: flex;
  align-items: center;
  gap: 12px;
}
.student-brief-card .student-avatar-sym {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 50%;
  font-size: 18px;
  font-weight: 700;
}
.student-brief-card h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}
.student-brief-card p {
  margin: 2px 0 0;
  font-size: 13px;
  color: var(--muted, #617387);
}
.academic-eligibility-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12.5px;
  font-weight: 600;
}
.academic-tag-eligible {
  background: #dcfce7;
  color: #15803d;
}
.academic-tag-ineligible {
  background: #fee2e2;
  color: #991b1b;
}

/* Bảng nguyện vọng chính */
.wishes-table-wrapper {
  background: white;
  border: 1px solid var(--border, #dde5ed);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.wishes-table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #ffffff;
  border-bottom: 1px solid var(--border, #dde5ed);
}
.wishes-table-header h2 {
  font-size: 17px;
  font-weight: 700;
  margin: 0;
  color: var(--ink, #1f2d3d);
}
.wishes-table-header span.quota-badge {
  font-size: 13px;
  font-weight: 600;
  color: var(--primary, #0284c7);
  background: #e0f2fe;
  padding: 4px 10px;
  border-radius: 14px;
}

.aspiration-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.aspiration-table th {
  background: #f8fafc;
  color: var(--muted, #617387);
  font-size: 12.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border, #dde5ed);
}
.aspiration-table td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border, #dde5ed);
  vertical-align: middle;
}
.aspiration-table tr:last-child td {
  border-bottom: none;
}
.aspiration-table tr:hover td {
  background: #fbfcfe;
}

.order-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 32px;
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-weight: 800;
  font-size: 13px;
}
.order-badge.nv1 {
  background: #eff6ff;
  color: #1d4ed8;
  border-color: #bfdbfe;
}
.order-badge.nv2 {
  background: #f0fdf4;
  color: #15803d;
  border-color: #bbf7d0;
}
.order-badge.nv3 {
  background: #fffbeb;
  color: #b45309;
  border-color: #fde68a;
}

.select-school, .select-track {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid var(--border, #cbd5e1);
  border-radius: 6px;
  font-size: 14px;
  background-color: #fff;
  color: var(--ink, #1f2d3d);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.select-school:focus, .select-track:focus {
  border-color: #0284c7;
  outline: none;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

.area-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  background: #f3f4f6;
  color: #4b5563;
}
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}
.status-pill.status-pending {
  background: #fef3c7;
  color: #92400e;
}
.status-pill.status-approved {
  background: #dcfce7;
  color: #166534;
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 6px;
}
.btn-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--border, #cbd5e1);
  background: white;
  color: var(--ink, #1f2d3d);
  cursor: pointer;
  font-size: 14px;
  transition: all 0.15s;
}
.btn-action-icon:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #94a3b8;
}
.btn-action-icon:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.btn-action-icon.btn-delete:hover:not(:disabled) {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

.table-empty-row {
  text-align: center;
  padding: 32px !important;
  color: var(--muted, #617387);
}

/* Bảng công cụ dưới bảng */
.wishes-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  padding: 14px 20px;
  background: #f8fafc;
  border-top: 1px solid var(--border, #dde5ed);
}

.rules-notice-box {
  background: #f8fafc;
  border-left: 4px solid #0284c7;
  border-radius: 0 8px 8px 0;
  padding: 16px 20px;
  margin-bottom: 24px;
  font-size: 13.5px;
  line-height: 1.6;
}
.rules-notice-box h4 {
  margin: 0 0 8px;
  font-size: 14.5px;
  color: #0369a1;
  display: flex;
  align-items: center;
  gap: 6px;
}
.rules-notice-box ul {
  margin: 0;
  padding-left: 20px;
  color: var(--ink, #334155);
}
.rules-notice-box li {
  margin-bottom: 4px;
}

.form-submit-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

/* Modal xác nhận xóa */
.custom-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.custom-modal {
  background: white;
  width: min(460px, calc(100% - 32px));
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}
.custom-modal h3 {
  margin: 0 0 10px;
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
}
.custom-modal p {
  margin: 0 0 20px;
  font-size: 14px;
  color: #64748b;
  line-height: 1.5;
}
.custom-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

@media (max-width: 768px) {
  .preconditions-card {
    grid-template-columns: 1fr;
  }
  .aspiration-table th, .aspiration-table td {
    padding: 10px 8px;
    font-size: 13px;
  }
  .wishes-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>

<div class="aspiration-page">

  <!-- Header thông tin màn hình -->
  <div class="aspiration-head">
    <div class="eyebrow">QUY CHẾ TUYỂN SINH LỚP 10 · NĂM HỌC 2026–2027</div>
    <h1>Quản lý nguyện vọng xét tuyển lớp 10 [LT1]</h1>
    <p>Học sinh đăng ký tối đa <?= $maxWishes ?> nguyện vọng vào các trường THPT công lập theo thứ tự ưu tiên. Thí sinh có thể thêm, bớt, chỉnh sửa và thay đổi thứ tự ưu tiên trước khi hết thời hạn quy định.</p>
  </div>

  <!-- Thông báo Flash Messages từ hệ thống -->
  <?php foreach (flash_take() as $flash): ?>
    <div class="ui-notice ui-notice-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status" style="margin-bottom: 20px;">
      <?= e($flash['message']) ?>
    </div>
  <?php endforeach; ?>

  <!-- Hộp thông báo lỗi realtime bằng JS -->
  <div id="js-error-alert" class="ui-notice ui-notice-error" role="alert" style="display: none; margin-bottom: 20px;">
    <strong id="js-error-message"></strong>
  </div>

  <!-- Thẻ kiểm tra 3 Tiền điều kiện (Preconditions) -->
  <div class="preconditions-card" aria-label="Kiểm tra tiền điều kiện của use case">
    <div class="precondition-item">
      <div class="precondition-icon icon-ok" title="Đã đăng nhập">✓</div>
      <div>
        <strong>1. Tài khoản học sinh</strong>
        <small><?= e($candidate['hoTen']) ?> (<?= e($candidate['soBaoDanh'] ?: $candidate['maThiSinh']) ?>) · Đã đăng nhập</small>
      </div>
    </div>
    <div class="precondition-item">
      <div class="precondition-icon <?= $isEligibleExam ? 'icon-ok' : 'icon-err' ?>">
        <?= $isEligibleExam ? '✓' : '!' ?>
      </div>
      <div>
        <strong>2. Đăng ký dự thi lớp 10</strong>
        <small>Trạng thái: <strong><?= e($candidate['trangThaiDangKyDuThi']) ?></strong> (<?= $isEligibleExam ? 'Đủ điều kiện dự thi' : 'Chưa đủ điều kiện' ?>)</small>
      </div>
    </div>
    <div class="precondition-item">
      <div class="precondition-icon <?= $isRegistrationOpen ? 'icon-ok' : 'icon-warn' ?>">
        <?= $isRegistrationOpen ? '✓' : '⏰' ?>
      </div>
      <div>
        <strong>3. Thời gian tiếp nhận</strong>
        <small>
          <?php if ($regulation): ?>
            <?= date('d/m/Y', strtotime($regulation['thoiGianBatDau'])) ?> – <?= date('d/m/Y', strtotime($regulation['thoiGianKetThuc'])) ?>
            <br><span style="color: <?= $isRegistrationOpen ? '#166534' : '#b45309' ?>; font-weight: 600;">
              <?= $isRegistrationOpen ? '● Đang mở nhận hồ sơ' : '● Đã hết hạn điều chỉnh' ?>
            </span>
          <?php else: ?>
            Chưa có lịch đăng ký
          <?php endif; ?>
        </small>
      </div>
    </div>
  </div>

  <?php if (!$canEdit): ?>
    <div class="ui-notice ui-notice-error" role="alert" style="margin-bottom: 20px;">
      <strong>Lưu ý:</strong> Hiện tại hệ thống đang ở chế độ <strong>Chỉ xem</strong> do chưa thỏa mãn tiền điều kiện (hết thời gian đăng ký hoặc hồ sơ dự thi chưa được duyệt). Bạn không thể thay đổi thông tin nguyện vọng lúc này.
    </div>
  <?php endif; ?>

  <!-- Thông tin hồ sơ & Tiêu chuẩn xét tuyển lớp chuyên -->
  <div class="student-brief-card">
    <div class="student-id-block">
      <span class="student-avatar-sym">♙</span>
      <div>
        <h3><?= e($candidate['hoTen']) ?></h3>
        <p>Mã HS: <strong><?= e($candidate['maThiSinh']) ?></strong> · SBD: <strong><?= e($candidate['soBaoDanh'] ?: 'Chưa cấp') ?></strong> · Trường THCS: <strong><?= e(school_display_name($candidate['tenTruongTHCS'] ?? '')) ?></strong></p>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 12.5px; color: var(--muted, #617387); margin-bottom: 4px;">Kết quả học tập lớp 9: ĐTB <strong><?= e($candidate['grade9']['diemTrungBinh'] ?? '—') ?></strong> · Học lực <strong><?= e($candidate['grade9']['hocLuc'] ?? '—') ?></strong> · Hạnh kiểm <strong><?= e($candidate['grade9']['hanhKiem'] ?? '—') ?></strong></div>
      <span class="academic-eligibility-tag <?= $candidate['eligible_specialized'] ? 'academic-tag-eligible' : 'academic-tag-ineligible' ?>">
        <?= $candidate['eligible_specialized'] ? '★ ' : '⚠ ' ?><?= e($candidate['specialized_reason']) ?>
      </span>
    </div>
  </div>

  <!-- Quy định đăng ký (Business Rules Info) -->
  <div class="rules-notice-box">
    <h4><span aria-hidden="true">ℹ</span> Hướng dẫn quy định đăng ký nguyện vọng lớp 10:</h4>
    <ul>
      <li><strong>Số lượng nguyện vọng:</strong> Thí sinh được đăng ký tối đa <strong><?= $maxWishes ?> nguyện vọng</strong> theo thứ tự ưu tiên (NV1 là ưu tiên cao nhất).</li>
      <li><strong>Quy định trùng lặp:</strong> Không được chọn cùng một trường THPT cho nhiều nguyện vọng hệ thường (Lớp thường).</li>
      <li><strong>Đăng ký lớp chuyên:</strong> Chỉ áp dụng tại các trường THPT có chỉ tiêu chuyên (THPT Sao Mai, THPT Bình Minh). Mỗi thí sinh đăng ký tối đa 2 nguyện vọng chuyên và cần đạt Học lực, Hạnh kiểm lớp 9 từ loại Khá trở lên.</li>
      <li><strong>Điều chỉnh thứ tự:</strong> Sử dụng các nút <strong>↑</strong> (Lên) hoặc <strong>↓</strong> (Xuống) để hoán đổi thứ tự ưu tiên giữa các nguyện vọng.</li>
    </ul>
  </div>

  <!-- Form Quản lý nguyện vọng (Interactive Table) -->
  <form id="wishes-form" method="post" action="<?= e(url('aspirations/')) ?>">
    <?= csrf_field() ?>

    <div class="wishes-table-wrapper">
      <div class="wishes-table-header">
        <h2>Danh sách nguyện vọng đăng ký</h2>
        <span class="quota-badge" id="wish-count-indicator">
          Đã chọn: <strong id="current-count"><?= count($currentWishes) ?></strong> / <?= $maxWishes ?> NV
        </span>
      </div>

      <div style="overflow-x: auto;">
        <table class="aspiration-table" id="wishes-table">
          <thead>
            <tr>
              <th style="width: 100px; text-align: center;">Thứ tự</th>
              <th style="min-width: 260px;">Trường THPT</th>
              <th style="min-width: 220px;">Hệ đào tạo / Lớp</th>
              <th style="width: 120px;">Khu vực</th>
              <th style="width: 130px;">Trạng thái</th>
              <th style="width: 140px; text-align: center;">Thao tác</th>
            </tr>
          </thead>
          <tbody id="wishes-tbody">
            <?php if (!$currentWishes): ?>
              <tr id="empty-row">
                <td colspan="6" class="table-empty-row">
                  Học sinh chưa đăng ký nguyện vọng nào. Hãy bấm nút <strong>"+ Thêm nguyện vọng mới"</strong> bên dưới để bắt đầu.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($currentWishes as $idx => $w):
                $order = $idx + 1;
                $orderClass = $order === 1 ? 'nv1' : ($order === 2 ? 'nv2' : 'nv3');
              ?>
                <tr class="wish-row" data-order="<?= $order ?>" data-initial-school="<?= e($w['maTruongTHPT']) ?>" data-initial-track="<?= e($w['lopChuyen']) ?>">
                  <td style="text-align: center;">
                    <span class="order-badge <?= $orderClass ?>" data-badge-order>NV <?= $order ?></span>
                    <input type="hidden" name="wishes[<?= $idx ?>][thuTuNguyenVong]" class="input-order" value="<?= $order ?>">
                    <input type="hidden" name="wishes[<?= $idx ?>][maNguyenVong]" value="<?= e($w['maNguyenVong']) ?>">
                  </td>
                  <td>
                    <select name="wishes[<?= $idx ?>][maTruongTHPT]" class="select-school" required <?= !$canEdit ? 'disabled' : '' ?>>
                      <option value="">-- Chọn trường THPT --</option>
                      <?php foreach ($schools as $sch): ?>
                        <option value="<?= e($sch['maTruongTHPT']) ?>"
                                data-area="<?= e($sch['tenKhuVuc']) ?>"
                                data-has-specialized="<?= $sch['has_specialized'] ? '1' : '0' ?>"
                                <?= $w['maTruongTHPT'] === $sch['maTruongTHPT'] ? 'selected' : '' ?>>
                          <?= e($sch['maTruongTHPT']) ?> - <?= e(school_display_name($sch['tenTruongTHPT'])) ?> (<?= e($sch['tenKhuVuc']) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <select name="wishes[<?= $idx ?>][lopChuyen]" class="select-track" required <?= !$canEdit ? 'disabled' : '' ?>>
                      <option value="Thường" <?= $w['lopChuyen'] === 'Thường' ? 'selected' : '' ?>>Lớp thường (Đại trà)</option>
                      <!-- Các lớp chuyên sẽ được cập nhật động qua JS hoặc load sẵn -->
                      <option value="Chuyên Toán" <?= $w['lopChuyen'] === 'Chuyên Toán' ? 'selected' : '' ?>>Lớp Chuyên Toán</option>
                      <option value="Chuyên Ngữ văn" <?= $w['lopChuyen'] === 'Chuyên Ngữ văn' ? 'selected' : '' ?>>Lớp Chuyên Ngữ văn</option>
                      <option value="Chuyên Tiếng Anh" <?= $w['lopChuyen'] === 'Chuyên Tiếng Anh' ? 'selected' : '' ?>>Lớp Chuyên Tiếng Anh</option>
                      <option value="Chuyên Tin học" <?= $w['lopChuyen'] === 'Chuyên Tin học' ? 'selected' : '' ?>>Lớp Chuyên Tin học</option>
                      <option value="Chuyên Vật lý" <?= $w['lopChuyen'] === 'Chuyên Vật lý' ? 'selected' : '' ?>>Lớp Chuyên Vật lý</option>
                      <option value="Chuyên Hóa học" <?= $w['lopChuyen'] === 'Chuyên Hóa học' ? 'selected' : '' ?>>Lớp Chuyên Hóa học</option>
                    </select>
                  </td>
                  <td>
                    <span class="area-badge" data-area-badge><?= e($w['tenKhuVuc'] ?? '—') ?></span>
                  </td>
                  <td>
                    <span class="status-pill status-pending">
                      <?= e($w['trangThaiNguyenVong']) ?>
                    </span>
                  </td>
                  <td style="text-align: center;">
                    <div class="action-buttons" style="justify-content: center;">
                      <button type="button" class="btn-action-icon btn-move-up" title="Chuyển lên trên" <?= (!$canEdit || $order === 1) ? 'disabled' : '' ?>>↑</button>
                      <button type="button" class="btn-action-icon btn-move-down" title="Chuyển xuống dưới" <?= (!$canEdit || $order === count($currentWishes)) ? 'disabled' : '' ?>>↓</button>
                      <button type="button" class="btn-action-icon btn-delete" title="Xóa nguyện vọng này" <?= !$canEdit ? 'disabled' : '' ?>>✕</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Thanh công cụ dưới bảng -->
      <div class="wishes-toolbar">
        <div>
          <!-- Alternative Flow 3.1: Thêm nguyện vọng mới -->
          <button type="button" id="btn-add-wish" class="btn btn-outline btn-sm" <?= (!$canEdit || count($currentWishes) >= $maxWishes) ? 'disabled' : '' ?>>
            + Thêm nguyện vọng mới (NV)
          </button>
          <small id="add-wish-hint" style="color: var(--muted, #617387); margin-left: 8px;">
            <?= count($currentWishes) >= $maxWishes ? 'Đã đạt giới hạn tối đa 3 nguyện vọng.' : 'Có thể thêm nguyện vọng tiếp theo.' ?>
          </small>
        </div>
        <div style="font-size: 13px; color: var(--muted, #617387);">
          Học sinh có thể sắp xếp lại vị trí ưu tiên bất kỳ lúc nào.
        </div>
      </div>
    </div>

    <!-- Thanh nút hành động Lưu & Hủy -->
    <?php if ($canEdit): ?>
      <div class="form-submit-bar">
        <!-- Alternative Flow 5.2: Hủy thao tác -->
        <button type="button" id="btn-reset-wishes" class="btn btn-outline">
          ↺ Hủy thay đổi / Khôi phục ban đầu
        </button>
        <!-- Basic Flow 5 & 6: Chọn lưu/cập nhật nguyện vọng -->
        <button type="submit" id="btn-submit-wishes" class="btn btn-primary">
          💾 Lưu / Cập nhật nguyện vọng
        </button>
      </div>
    <?php endif; ?>
  </form>

</div>

<!-- Modal xác nhận xóa nguyện vọng (Alternative Flow 5.1) -->
<div id="delete-modal-backdrop" class="custom-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title">
  <div class="custom-modal">
    <h3 id="delete-dialog-title">Xác nhận xóa nguyện vọng</h3>
    <p id="delete-dialog-desc">
      Bạn có chắc chắn muốn xóa nguyện vọng này? Sau khi xóa, thứ tự các nguyện vọng phía sau sẽ tự động được dồn lên để đảm bảo tính liên tục.
    </p>
    <div class="custom-modal-actions">
      <button type="button" id="btn-modal-cancel" class="btn btn-outline btn-sm">Hủy bỏ</button>
      <button type="button" id="btn-modal-confirm" class="btn btn-danger btn-sm">Đồng ý xóa</button>
    </div>
  </div>
</div>

<!-- Dữ liệu trường học JSON dùng cho Client-side JS -->
<script>
window.__SCHOOLS_DATA = <?= json_encode($schools, JSON_UNESCAPED_UNICODE) ?>;
window.__MAX_WISHES = <?= $maxWishes ?>;
window.__CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
window.__ELIGIBLE_SPECIALIZED = <?= $candidate['eligible_specialized'] ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', function() {
  const tbody = document.getElementById('wishes-tbody');
  const btnAdd = document.getElementById('btn-add-wish');
  const addHint = document.getElementById('add-wish-hint');
  const currentCountEl = document.getElementById('current-count');
  const wishesForm = document.getElementById('wishes-form');
  const btnReset = document.getElementById('btn-reset-wishes');
  const errorAlert = document.getElementById('js-error-alert');
  const errorMessage = document.getElementById('js-error-message');

  // Modal elements
  const modalBackdrop = document.getElementById('delete-modal-backdrop');
  const btnModalCancel = document.getElementById('btn-modal-cancel');
  const btnModalConfirm = document.getElementById('btn-modal-confirm');
  let rowToDelete = null;

  // Lưu trạng thái HTML ban đầu để phục vụ Alternative Flow 5.2 (Hủy thao tác)
  const initialTbodyHtml = tbody.innerHTML;

  function showError(msg) {
    if (!msg) {
      errorAlert.style.display = 'none';
      errorMessage.textContent = '';
      return;
    }
    errorMessage.textContent = msg;
    errorAlert.style.display = 'block';
    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function clearError() {
    showError('');
  }

  // Cập nhật lại số thứ tự, badge màu sắc, nút Lên/Xuống của tất cả các hàng
  function reindexRows() {
    const rows = Array.from(tbody.querySelectorAll('.wish-row'));
    const total = rows.length;

    currentCountEl.textContent = total;

    if (total === 0) {
      tbody.innerHTML = `
        <tr id="empty-row">
          <td colspan="6" class="table-empty-row">
            Học sinh chưa đăng ký nguyện vọng nào. Hãy bấm nút <strong>"+ Thêm nguyện vọng mới"</strong> bên dưới để bắt đầu.
          </td>
        </tr>
      `;
      if (btnAdd) btnAdd.disabled = !window.__CAN_EDIT;
      if (addHint) addHint.textContent = 'Có thể thêm nguyện vọng tiếp theo.';
      return;
    }

    const emptyRow = document.getElementById('empty-row');
    if (emptyRow) emptyRow.remove();

    rows.forEach((row, idx) => {
      const order = idx + 1;
      row.dataset.order = order;

      // Badge thứ tự
      const badge = row.querySelector('[data-badge-order]');
      if (badge) {
        badge.textContent = 'NV ' + order;
        badge.className = 'order-badge ' + (order === 1 ? 'nv1' : (order === 2 ? 'nv2' : 'nv3'));
      }

      // Input ẩn
      const inputOrder = row.querySelector('.input-order');
      if (inputOrder) {
        inputOrder.name = `wishes[${idx}][thuTuNguyenVong]`;
        inputOrder.value = order;
      }

      const schoolSelect = row.querySelector('.select-school');
      if (schoolSelect) schoolSelect.name = `wishes[${idx}][maTruongTHPT]`;

      const trackSelect = row.querySelector('.select-track');
      if (trackSelect) trackSelect.name = `wishes[${idx}][lopChuyen]`;

      // Nút Lên / Xuống
      const btnUp = row.querySelector('.btn-move-up');
      const btnDown = row.querySelector('.btn-move-down');
      if (btnUp) btnUp.disabled = !window.__CAN_EDIT || (order === 1);
      if (btnDown) btnDown.disabled = !window.__CAN_EDIT || (order === total);
    });

    // Cập nhật trạng thái nút thêm
    if (btnAdd) {
      const reachedMax = total >= window.__MAX_WISHES;
      btnAdd.disabled = !window.__CAN_EDIT || reachedMax;
      if (addHint) {
        addHint.textContent = reachedMax
          ? `Đã đạt tối đa ${window.__MAX_WISHES} nguyện vọng theo quy định.`
          : `Có thể thêm nguyện vọng tiếp theo (${total}/${window.__MAX_WISHES}).`;
      }
    }
  }

  // Cập nhật khu vực và lọc các chuyên ngành phù hợp khi đổi trường
  function handleSchoolChange(row) {
    const schoolSelect = row.querySelector('.select-school');
    const trackSelect = row.querySelector('.select-track');
    const areaBadge = row.querySelector('[data-area-badge]');

    const selectedCode = schoolSelect.value;
    const schoolData = window.__SCHOOLS_DATA.find(s => s.maTruongTHPT === selectedCode);

    if (!schoolData) {
      if (areaBadge) areaBadge.textContent = '—';
      return;
    }

    if (areaBadge) areaBadge.textContent = schoolData.tenKhuVuc || '—';

    // Cập nhật dropdown lớp học
    const currentTrack = trackSelect.value;
    trackSelect.innerHTML = '';

    const availableTracks = schoolData.available_tracks || { 'Thường': 'Lớp thường (Đại trà)' };
    for (const [tKey, tLabel] of Object.entries(availableTracks)) {
      const opt = document.createElement('option');
      opt.value = tKey;
      opt.textContent = tLabel;
      if (tKey === currentTrack) opt.selected = true;
      trackSelect.appendChild(opt);
    }

    // Nếu track cũ không tồn tại ở trường mới, mặc định về Lớp thường
    if (!availableTracks[currentTrack]) {
      trackSelect.value = 'Thường';
    }
  }

  // Ủy quyền sự kiện (Event Delegation) trên tbody
  tbody.addEventListener('change', function(e) {
    clearError();
    const row = e.target.closest('.wish-row');
    if (!row) return;

    if (e.target.classList.contains('select-school')) {
      handleSchoolChange(row);
    }
  });

  tbody.addEventListener('click', function(e) {
    clearError();
    const row = e.target.closest('.wish-row');
    if (!row) return;

    // Di chuyển lên (Swap với hàng trước)
    if (e.target.closest('.btn-move-up')) {
      const prev = row.previousElementSibling;
      if (prev && prev.classList.contains('wish-row')) {
        tbody.insertBefore(row, prev);
        reindexRows();
      }
      return;
    }

    // Di chuyển xuống (Swap với hàng sau)
    if (e.target.closest('.btn-move-down')) {
      const next = row.nextElementSibling;
      if (next && next.classList.contains('wish-row')) {
        tbody.insertBefore(next, row);
        reindexRows();
      }
      return;
    }

    // Alternative Flow 5.1: Xóa nguyện vọng với modal xác nhận
    if (e.target.closest('.btn-delete')) {
      rowToDelete = row;
      const order = row.dataset.order;
      const schoolSelect = row.querySelector('.select-school');
      const schoolText = schoolSelect.options[schoolSelect.selectedIndex]?.text || '';

      document.getElementById('delete-dialog-desc').textContent =
        `Bạn có chắc chắn muốn xóa Nguyện vọng ${order} ${schoolText ? '(' + schoolText + ')' : ''}? Sau khi xóa, các nguyện vọng phía sau sẽ tự động dồn lên.`;

      modalBackdrop.style.display = 'flex';
      return;
    }
  });

  // Xử lý xác nhận xóa trong Modal
  btnModalCancel.addEventListener('click', function() {
    modalBackdrop.style.display = 'none';
    rowToDelete = null;
  });

  btnModalConfirm.addEventListener('click', function() {
    if (rowToDelete) {
      rowToDelete.remove();
      rowToDelete = null;
      reindexRows();
    }
    modalBackdrop.style.display = 'none';
  });

  // Alternative Flow 3.1: Thêm nguyện vọng mới
  if (btnAdd) {
    btnAdd.addEventListener('click', function() {
      clearError();
      const currentRows = tbody.querySelectorAll('.wish-row');
      if (currentRows.length >= window.__MAX_WISHES) {
        showError(`Đã đạt giới hạn tối đa ${window.__MAX_WISHES} nguyện vọng theo quy định.`);
        return;
      }

      const emptyRow = document.getElementById('empty-row');
      if (emptyRow) emptyRow.remove();

      const nextOrder = currentRows.length + 1;
      const newIndex = nextOrder - 1;

      // Sinh danh sách option các trường
      let schoolOptions = '<option value="">-- Chọn trường THPT --</option>';
      window.__SCHOOLS_DATA.forEach(s => {
        schoolOptions += `<option value="${s.maTruongTHPT}" data-area="${s.tenKhuVuc}">${s.maTruongTHPT} - ${s.tenTruongTHPT} (${s.tenKhuVuc})</option>`;
      });

      const tr = document.createElement('tr');
      tr.className = 'wish-row';
      tr.dataset.order = nextOrder;
      tr.innerHTML = `
        <td style="text-align: center;">
          <span class="order-badge nv${nextOrder}" data-badge-order>NV ${nextOrder}</span>
          <input type="hidden" name="wishes[${newIndex}][thuTuNguyenVong]" class="input-order" value="${nextOrder}">
        </td>
        <td>
          <select name="wishes[${newIndex}][maTruongTHPT]" class="select-school" required>
            ${schoolOptions}
          </select>
        </td>
        <td>
          <select name="wishes[${newIndex}][lopChuyen]" class="select-track" required>
            <option value="Thường">Lớp thường (Đại trà)</option>
          </select>
        </td>
        <td>
          <span class="area-badge" data-area-badge>—</span>
        </td>
        <td>
          <span class="status-pill status-pending">Mới nhập</span>
        </td>
        <td style="text-align: center;">
          <div class="action-buttons" style="justify-content: center;">
            <button type="button" class="btn-action-icon btn-move-up" title="Chuyển lên trên">↑</button>
            <button type="button" class="btn-action-icon btn-move-down" title="Chuyển xuống dưới" disabled>↓</button>
            <button type="button" class="btn-action-icon btn-delete" title="Xóa nguyện vọng này">✕</button>
          </div>
        </td>
      `;

      tbody.appendChild(tr);
      reindexRows();

      // Cuộn đến dòng mới
      tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  }

  // Alternative Flow 5.2: Học sinh chọn hủy thao tác
  if (btnReset) {
    btnReset.addEventListener('click', function() {
      if (confirm('Bạn có chắc chắn muốn hủy tất cả các thay đổi vừa chỉnh sửa và khôi phục lại danh sách nguyện vọng ban đầu không?')) {
        clearError();
        tbody.innerHTML = initialTbodyHtml;
        reindexRows();
      }
    });
  }

  // Basic Flow 4 & Exception Flow 4.1: Kiểm tra tính hợp lệ trước khi submit form
  if (wishesForm) {
    wishesForm.addEventListener('submit', function(e) {
      clearError();
      const rows = Array.from(tbody.querySelectorAll('.wish-row'));

      if (rows.length === 0) {
        e.preventDefault();
        showError('Học sinh phải đăng ký ít nhất 1 nguyện vọng.');
        return;
      }

      if (rows.length > window.__MAX_WISHES) {
        e.preventDefault();
        showError(`Số lượng nguyện vọng không được vượt quá ${window.__MAX_WISHES}.`);
        return;
      }

      const seenSchoolsNormal = new Map();
      const seenExact = new Set();
      let specializedCount = 0;

      for (let i = 0; i < rows.length; i++) {
        const order = i + 1;
        const row = rows[i];
        const schoolSelect = row.querySelector('.select-school');
        const trackSelect = row.querySelector('.select-track');

        const schoolCode = schoolSelect.value.trim();
        const schoolName = schoolSelect.options[schoolSelect.selectedIndex]?.text || schoolCode;
        const track = trackSelect.value.trim() || 'Thường';

        if (!schoolCode) {
          e.preventDefault();
          showError(`Vui lòng chọn trường THPT cho Nguyện vọng ${order}.`);
          schoolSelect.focus();
          return;
        }

        // Kiểm tra trùng trường lớp thường
        if (track === 'Thường') {
          if (seenSchoolsNormal.has(schoolCode)) {
            e.preventDefault();
            const prevOrder = seenSchoolsNormal.get(schoolCode);
            showError(`Không được đăng ký trùng trường cho các nguyện vọng hệ thường (Nguyện vọng ${prevOrder} và Nguyện vọng ${order} đều chọn ${schoolName}).`);
            schoolSelect.focus();
            return;
          }
          seenSchoolsNormal.set(schoolCode, order);
        }

        // Kiểm tra trùng lặp tuyệt đối
        const exactKey = `${schoolCode}___${track}`;
        if (seenExact.has(exactKey)) {
          e.preventDefault();
          showError(`Nguyện vọng ${order} bị trùng lặp hoàn toàn với một nguyện vọng trước đó (${schoolName} - ${track}).`);
          schoolSelect.focus();
          return;
        }
        seenExact.add(exactKey);

        // Kiểm tra điều kiện chuyên
        if (track !== 'Thường') {
          specializedCount++;
          if (specializedCount > 2) {
            e.preventDefault();
            showError(`Theo quy định của Sở GD&ĐT, mỗi thí sinh chỉ được đăng ký tối đa 2 nguyện vọng chuyên.`);
            trackSelect.focus();
            return;
          }

          if (!window.__ELIGIBLE_SPECIALIZED) {
            e.preventDefault();
            showError(`Bạn không đủ điều kiện đăng ký lớp chuyên tại Nguyện vọng ${order} do xếp loại học tập/hạnh kiểm lớp 9 chưa đạt yêu cầu.`);
            trackSelect.focus();
            return;
          }
        }
      }

      // Xác nhận lần cuối trước khi ghi CSDL
      const confirmed = confirm('Bạn có chắc chắn muốn lưu và cập nhật danh sách nguyện vọng xét tuyển lớp 10 này không?');
      if (!confirmed) {
        e.preventDefault();
      }
    });
  }

  // Khởi tạo reindex ban đầu
  reindexRows();
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

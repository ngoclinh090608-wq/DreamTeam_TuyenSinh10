<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/candidate_number_service.php';
require_once dirname(__DIR__) . '/services/exam_service.php';

// Support quick demo access if requested
if (isset($_GET['demo_as_bantuyensinh']) || isset($_GET['demo_state'])) {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    if (empty($_SESSION['user'])) {
        $_SESSION['user'] = [
            'maTaiKhoan' => 'TK_BANTUYENSINH',
            'tenDangNhap' => 'bantuyensinh',
            'hoTen' => 'Ban Tuyển sinh Sở GD&ĐT',
            'vaiTro' => 'BAN_TUYEN_SINH',
            'maThiSinh' => null,
        ];
        $_SESSION['last_seen'] = time();
    }
}

$user = current_user();
if (!$user) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
    } else {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        ui_redirect('auth/login.php', $next !== '' ? ['next' => $next] : []);
    }
} elseif (!has_role($user, ['BAN_TUYEN_SINH', 'QUAN_TRI'])) {
    tu_choi_truy_cap('candidates');
}

$error = null;
$details = [];
$rows = [];
$councils = [];
$totalEligible = 0;
$totalPending = 0;
$totalIssued = 0;

$council = ui_input('hoiDong');
$state = ui_input('trangThai') ?: 'pending';
$searchQuery = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';

// 1. Kiểm tra thời hạn chốt hồ sơ (Ngoại lệ 2.1)
$regWindow = candidate_number_check_registration_window();
$registrationBlocked = $regWindow['is_open'];

try {
    if (live_available()) {
        $councils = db()->query('SELECT h.maHoiDongThi, h.tenHoiDongThi, k.tenKyThi FROM HoiDongThi h JOIN KyThi k ON k.maKyThi=h.maKyThi ORDER BY h.maKyThi, h.maHoiDongThi')->fetchAll();
    }
} catch (Throwable $e) {
    error_log('councils: ' . $e->getMessage());
}
if (!$councils) {
    $councils = [
        ['maHoiDongThi' => 'HD001', 'tenHoiDongThi' => 'Hội đồng thi Quận 1 - THPT Sao Mai', 'tenKyThi' => 'Tuyển sinh 10 năm học 2026–2027'],
        ['maHoiDongThi' => 'HD002', 'tenHoiDongThi' => 'Hội đồng thi Quận 3 - THPT Bình Minh', 'tenKyThi' => 'Tuyển sinh 10 năm học 2026–2027'],
        ['maHoiDongThi' => 'HD003', 'tenHoiDongThi' => 'Hội đồng thi Quận 5 - THPT Hòa An', 'tenKyThi' => 'Tuyển sinh 10 năm học 2026–2027'],
    ];
}

$exam = candidate_number_exam_scope($council);

// Xử lý các yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($csrf = ui_csrf_error()) throw new BusinessException($csrf);
        if (ui_input('confirm') !== '1') throw new BusinessException('Vui lòng xác nhận thao tác.');

        $action = ui_input('action') ?: 'issue';
        if (!in_array($action, ['issue', 'issue_scope', 'revoke', 'revoke_scope', 'revoke_all'], true)) {
            throw new BusinessException('Thao tác không hợp lệ.');
        }

        // Ngoại lệ 2.1: Chặn cấp SBD khi thời gian đăng ký chưa chốt
        if (in_array($action, ['issue', 'issue_scope'], true) && $registrationBlocked) {
            throw new BusinessException('Chưa kết thúc thời gian chốt hồ sơ. Không thể thực hiện cấp SBD lúc này.');
        }

        // Ngoại lệ 6.1: Kiểm tra lỗi dữ liệu nếu đang test demo_state=data_error
        if (in_array($action, ['issue', 'issue_scope'], true) && isset($_GET['demo_state']) && $_GET['demo_state'] === 'data_error') {
            throw new BusinessException('Lỗi dữ liệu thí sinh: Phát hiện hồ sơ bị thiếu thông tin bắt buộc hoặc chưa được phân phòng thi. Quá trình cấp SBD đã dừng lại.');
        }

        if ($action === 'revoke_all') {
            // Alternative Flow 7.1: Hủy/Thu hồi toàn bộ SBD (Reset)
            $count = candidate_revoke_all($user, $council, true);
            flash_set('success', 'Đã hủy và thu hồi toàn bộ số báo danh (' . $count . ' thí sinh). Toàn bộ thí sinh được đưa về trạng thái "Chưa có SBD".');
            ui_redirect('candidates/cap-so-bao-danh.php', ['hoiDong' => $council, 'trangThai' => 'pending']);
        }

        $ids = $_POST['candidates'] ?? [];
        if (!is_array($ids)) throw new BusinessException('Danh sách thí sinh không hợp lệ.');

        if ($action === 'issue_scope') {
            // Basic Flow Bước 4, 5, 6: Cấp cho toàn bộ thí sinh trong phạm vi đang chờ cấp
            $pendingList = candidate_number_directory($council, 'pending');
            if (empty($pendingList)) {
                throw new BusinessException('Không có thí sinh nào đang chờ cấp số báo danh.');
            }
            $ids = array_column($pendingList, 'maThiSinh');
        } elseif ($action === 'revoke_scope') {
            $issuedList = candidate_number_directory($council, 'issued');
            if (empty($issuedList)) {
                throw new BusinessException('Không có thí sinh nào để thu hồi số báo danh.');
            }
            $ids = array_column($issuedList, 'maThiSinh');
        }

        if (empty($ids)) {
            throw new BusinessException('Vui lòng chọn ít nhất một thí sinh để thực hiện.');
        }

        if (in_array($action, ['revoke', 'revoke_scope'], true)) {
            $count = candidate_revoke_numbers($user, $ids, true);
            flash_set('success', 'Đã thu hồi số báo danh của ' . $count . ' thí sinh.');
            ui_redirect('candidates/cap-so-bao-danh.php', ['hoiDong' => $council, 'trangThai' => 'pending']);
        } else {
            // Basic Flow Bước 6 & 7: Cấp SBD
            $issued = candidate_issue_numbers($user, $ids, $exam);
            flash_set('success', 'Đã cấp Số báo danh thành công cho ' . count($issued) . ' thí sinh.');
            ui_redirect('candidates/cap-so-bao-danh.php', ['hoiDong' => $council, 'trangThai' => 'issued']);
        }
    } catch (BusinessException $e) {
        $error = $e->getMessage();
        $details = $e->details();
    } catch (Throwable $e) {
        error_log('SBD: ' . $e->getMessage());
        $error = 'Không thể xử lý số báo danh. Giao dịch lỗi không lưu thay đổi.';
    }
}

// 2. Tải danh sách thí sinh theo bộ lọc
try {
    $rows = candidate_number_directory($council, $state, $searchQuery);
} catch (Throwable $e) {
    error_log('SBD load rows: ' . $e->getMessage());
    $rows = [];
}

// Alternative Flow 7.2: Xuất danh sách SBD (CSV)
if (ui_input('export') === '1') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="danh-sach-so-bao-danh.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
    fputcsv($out, ['STT', 'Mã thí sinh', 'Mã hồ sơ', 'Họ và tên', 'Ngày sinh', 'Số báo danh', 'Điểm thi', 'Phòng thi', 'Trường THCS', 'Trạng thái SBD']);
    $stt = 1;
    foreach ($rows as $row) {
        $sbdText = $row['soBaoDanh'] ?? '';
        $cells = [
            $stt++,
            $row['maThiSinh'],
            $row['maHoSo'],
            $row['hoTen'],
            $row['ngaySinh'] ?? '',
            $sbdText !== '' ? $sbdText : 'Chưa có SBD',
            $row['diemThi'] ?? '',
            $row['phongThi'] ?? 'Chưa phân phòng',
            $row['tenTruongTHCS'] ?? '',
            $sbdText !== '' ? 'Đã cấp SBD' : 'Chờ cấp SBD',
        ];
        fputcsv($out, array_map(static fn($c) => preg_match('/\A[=+@\-\t\r\n]/u', (string)$c) ? "'" . $c : $c, $cells));
    }
    fclose($out);
    exit;
}

// 3. Tính toán số liệu tổng thể (Basic Flow Bước 2)
try {
    $allCandidatesInScope = candidate_number_directory($council, 'all');
    foreach ($allCandidatesInScope as $cand) {
        $totalEligible++;
        if (empty($cand['soBaoDanh'])) {
            $totalPending++;
        } else {
            $totalIssued++;
        }
    }
} catch (Throwable $e) {
    error_log('SBD stats: ' . $e->getMessage());
}

// Kiểm tra Ngoại lệ 2.2: Toàn bộ thí sinh đã có SBD
$isAllIssued = ($totalPending === 0 && $totalEligible > 0);

// Kiểm tra Ngoại lệ 6.1: Lỗi dữ liệu thí sinh
$dataErrors = candidate_number_check_data_errors($rows);

ui_page_start('candidates', 'Cấp số báo danh cho thí sinh', 'Phân hệ Quản lý kỳ thi', 'Đánh số báo danh tự động theo quy tắc A–B–C sau khi chốt thời gian đăng ký và hoàn tất phân phòng thi.', 'UC03');
ui_flashes();
if ($error) ui_notice('error', $error, $details);
?>

<div class="sbd-wrapper">
  <!-- Demo Toolbar: Hỗ trợ kiểm thử nhanh tất cả các luồng theo Use Case 03 -->
  <aside class="demo-flow-toolbar" aria-label="Thanh công cụ kiểm thử nhanh Use Case 3">
    <div class="toolbar-label">
      <span aria-hidden="true">⚙</span>
      <span>Kiểm thử UC03 (Cấp số báo danh):</span>
    </div>
    <div class="toolbar-actions">
      <a class="toolbar-chip<?= (!isset($_GET['demo_state']) && $state === 'pending') ? ' is-active' : '' ?>"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['trangThai' => 'pending'])) ?>"
         title="Luồng cơ bản: Xem danh sách chờ cấp và thực hiện cấp SBD với Modal xác nhận">
        Basic Flow (Chờ cấp SBD)
      </a>
      <a class="toolbar-chip<?= ($state === 'issued') ? ' is-active' : '' ?>"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['trangThai' => 'issued'])) ?>"
         title="Xem danh sách các thí sinh đã được cấp SBD">
        Danh sách đã có SBD
      </a>
      <a class="toolbar-chip"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['trangThai' => $state, 'export' => '1'])) ?>"
         title="Luồng thay thế 7.2: Xuất danh sách SBD định dạng CSV">
        Alternative Flow 7.2 (Xuất CSV)
      </a>
      <a class="toolbar-chip<?= (isset($_GET['demo_state']) && $_GET['demo_state'] === 'registration_open') ? ' is-active' : '' ?>"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['demo_state' => 'registration_open'])) ?>"
         title="Luồng ngoại lệ 2.1: Chưa kết thúc thời gian chốt hồ sơ">
        Exception Flow 2.1 (Đang mở đăng ký)
      </a>
      <a class="toolbar-chip<?= (isset($_GET['demo_state']) && $_GET['demo_state'] === 'data_error') ? ' is-active' : '' ?>"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['demo_state' => 'data_error'])) ?>"
         title="Luồng ngoại lệ 6.1: Lỗi dữ liệu thí sinh thiếu thông tin/chưa gắn phòng">
        Exception Flow 6.1 (Lỗi dữ liệu)
      </a>
      <a class="toolbar-chip<?= (isset($_GET['demo_state']) && $_GET['demo_state'] === 'all_issued') ? ' is-active' : '' ?>"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['demo_state' => 'all_issued', 'trangThai' => 'all'])) ?>"
         title="Luồng ngoại lệ 2.2: Toàn bộ thí sinh đã có Số báo danh">
        Exception Flow 2.2 (Đã có đủ SBD)
      </a>
      <a class="toolbar-chip"
         href="<?= e(url('candidates/cap-so-bao-danh.php', ['demo_state' => 'pending', 'trangThai' => 'pending'])) ?>"
         title="Khôi phục trạng thái chờ cấp SBD để làm lại từ đầu">
        ↺ Làm mới dữ liệu demo
      </a>
    </div>
  </aside>

  <!-- ====================================================================
       BASIC FLOW - BƯỚC 2: Hiển thị thông tin quản lý cấp SBD & Chỉ số KPI
       ==================================================================== -->
  <header class="admission-header-card" style="margin-bottom:20px">
    <div class="header-top">
      <div>
        <span class="section-overline">BAN TUYỂN SINH SỞ GD&ĐT · PHÂN HỆ QUẢN LÝ KỲ THI</span>
        <h1 style="margin:4px 0 8px">Cấp số báo danh cho thí sinh</h1>
        <p class="header-desc">
          Tự động đánh số báo danh duy nhất theo thứ tự tiếng Việt A–B–C sau khi kết thúc thời gian đăng ký và hoàn tất phân phòng thi.
        </p>
      </div>
      <div>
        <?php if ($registrationBlocked): ?>
          <span class="sbd-badge-pending" style="font-size:13px;padding:6px 14px">⚠️ Đang mở đăng ký hồ sơ</span>
        <?php elseif ($isAllIssued): ?>
          <span class="sbd-badge-issued" style="font-size:13px;padding:6px 14px">✅ Hoàn tất cấp SBD (100%)</span>
        <?php else: ?>
          <span class="sbd-badge-pending" style="font-size:13px;padding:6px 14px">⏳ Đang chờ cấp SBD (còn <?= $totalPending ?>)</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- 4 ô thống kê quản lý cấp SBD (Bước 2) -->
    <div class="admission-quick-stats">
      <div class="admission-stat-box">
        <span class="stat-label">Tổng thí sinh hợp lệ</span>
        <span class="stat-value"><?= number_format($totalEligible, 0, ',', '.') ?></span>
        <span class="stat-sub">Đã duyệt & đủ điều kiện</span>
      </div>
      <div class="admission-stat-box">
        <span class="stat-label">Chưa có số báo danh</span>
        <span class="stat-value" style="color:<?= $totalPending > 0 ? '#d32f2f' : '#2e7d32' ?>">
          <?= number_format($totalPending, 0, ',', '.') ?>
        </span>
        <span class="stat-sub">Hồ sơ đang chờ đánh số</span>
      </div>
      <div class="admission-stat-box">
        <span class="stat-label">Đã có số báo danh</span>
        <span class="stat-value" style="color:#2e7d32">
          <?= number_format($totalIssued, 0, ',', '.') ?>
        </span>
        <span class="stat-sub">Đã gán SBD duy nhất</span>
      </div>
      <div class="admission-stat-box">
        <span class="stat-label">Tiến độ cấp SBD</span>
        <span class="stat-value">
          <?= $totalEligible > 0 ? round(($totalIssued / $totalEligible) * 100, 1) : 0 ?>%
        </span>
        <span class="stat-sub"><?= $isAllIssued ? 'Đã hoàn tất toàn bộ' : 'Sẵn sàng xử lý' ?></span>
      </div>
    </div>
  </header>

  <!-- ====================================================================
       BASIC FLOW - BƯỚC 2: Các tiêu chí đánh số báo danh (A-B-C, quy cách SBD)
       ==================================================================== -->
  <section class="sbd-rules-card" aria-labelledby="sbd-criteria-title">
    <h3 id="sbd-criteria-title">
      <span aria-hidden="true">📋</span> Tiêu chí và quy tắc đánh số báo danh (Quy chế tuyển sinh lớp 10)
    </h3>
    <div class="sbd-rules-grid">
      <div class="sbd-rule-item">
        <strong><span>🔤</span> Thứ tự sắp xếp A–B–C</strong>
        <p>Danh sách thí sinh được sắp xếp theo bảng chữ cái tiếng Việt của <strong>Tên → Họ và tên đệm → Ngày sinh</strong>.</p>
      </div>
      <div class="sbd-rule-item">
        <strong><span>🔢</span> Định dạng SBD quy chuẩn</strong>
        <p>Số báo danh gồm tiền tố <strong>SBD</strong> kết hợp 8 chữ số nối tiếp (ví dụ: <code>SBD600001</code>, <code>SBD600002</code>...).</p>
      </div>
      <div class="sbd-rule-item">
        <strong><span>🔒</span> Tính duy nhất & Nối tiếp</strong>
        <p>Mỗi thí sinh có đúng 1 SBD duy nhất. Cấp bổ sung (Luồng 3.1) sẽ sinh số nối tiếp, tuyệt đối không đổi SBD đã cấp.</p>
      </div>
      <div class="sbd-rule-item">
        <strong><span>🔗</span> Liên kết điểm thi & phòng thi</strong>
        <p>SBD gắn liền với thông tin Điểm thi và Phòng thi hợp lệ để hỗ trợ tra cứu điện tử và in Giấy báo dự thi.</p>
      </div>
    </div>
  </section>

  <!-- ====================================================================
       EXCEPTION FLOW 2.1: Chưa kết thúc thời gian đăng ký (Ngoại lệ ở Bước 2)
       1. Hệ thống kiểm tra và phát hiện thời gian đăng ký vẫn đang mở.
       2. Hiển thị cảnh báo “Chưa kết thúc thời gian chốt hồ sơ. Không thể thực hiện cấp SBD lúc này.”
       3. Không cho phép Ban tuyển sinh thực hiện cấp SBD.
       ==================================================================== -->
  <?php if ($registrationBlocked): ?>
    <section class="sbd-alert sbd-alert-warning" role="alert">
      <div class="alert-icon" aria-hidden="true">⚠️</div>
      <div>
        <h3>Chưa kết thúc thời gian chốt hồ sơ. Không thể thực hiện cấp SBD lúc này.</h3>
        <p>
          Hệ thống phát hiện thời gian đăng ký nguyện vọng của thí sinh vẫn đang mở (thời hạn chốt dự kiến: <strong><?= e($regWindow['deadline']) ?></strong>).
        </p>
        <p style="margin:0;font-size:13.5px;color:var(--muted)">
          Hệ thống chỉ cho phép cấp số báo danh sau khi đã khóa sổ đăng ký, chốt dữ liệu và hoàn tất xếp phòng thi. Nút thực hiện cấp số báo danh tạm thời bị khóa.
        </p>
      </div>
    </section>
  <?php endif; ?>

  <!-- ====================================================================
       EXCEPTION FLOW 2.2: Toàn bộ thí sinh đã có Số báo danh
       1. Hệ thống nhận diện toàn bộ thí sinh hợp lệ đã được cấp SBD.
       2. Hệ thống thông báo trạng thái “Hoàn tất”.
       3. Không cho phép thực hiện lại chức năng cấp SBD hàng loạt.
       ==================================================================== -->
  <?php if ($isAllIssued && !$registrationBlocked): ?>
    <section class="sbd-alert sbd-alert-success" role="status">
      <div class="alert-icon" aria-hidden="true">✅</div>
      <div>
        <h3>Trạng thái Hoàn tất: Toàn bộ <?= $totalEligible ?> thí sinh hợp lệ đã được cấp Số báo danh</h3>
        <p>
          Tất cả thí sinh đủ điều kiện dự thi trong phạm vi quản lý đã có số báo danh duy nhất theo đúng quy tắc A–B–C. Hệ thống không cho phép thực hiện lại cấp SBD hàng loạt để tránh trùng lặp hoặc xáo trộn dữ liệu đã công bố.
        </p>
        <div class="alert-actions">
          <a class="btn btn-primary btn-sm" href="<?= e(url('candidates/cap-so-bao-danh.php', ['hoiDong' => $council, 'trangThai' => 'issued', 'export' => '1'])) ?>">
            <span aria-hidden="true">📥</span> Xuất danh sách SBD (CSV)
          </a>
          <button type="button" class="btn btn-outline btn-sm" data-student-dialog-open="modal-revoke-all">
            <span aria-hidden="true">↺</span> Hủy / Thu hồi toàn bộ SBD (Reset)
          </button>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- ====================================================================
       EXCEPTION FLOW 6.1: Lỗi dữ liệu thí sinh
       1. Hệ thống phát hiện có thí sinh bị thiếu thông tin bắt buộc hoặc chưa gắn phòng thi.
       2. Dừng quá trình cấp SBD.
       3. Thông báo lỗi và cung cấp danh sách các mã hồ sơ bị lỗi để rà soát.
       ==================================================================== -->
  <?php if (!empty($dataErrors) && (isset($_GET['demo_state']) && $_GET['demo_state'] === 'data_error')): ?>
    <section class="sbd-alert sbd-alert-danger" role="alert">
      <div class="alert-icon" aria-hidden="true">❌</div>
      <div>
        <h3>Lỗi dữ liệu thí sinh: Dừng quá trình cấp SBD</h3>
        <p>
          Hệ thống phát hiện có thí sinh bị thiếu thông tin bắt buộc hoặc chưa được phân phòng thi hợp lệ. Quá trình cấp SBD đã dừng lại để đảm bảo tính toàn vẹn dữ liệu.
        </p>
        <p style="font-weight:600;margin-bottom:6px">Danh sách các mã hồ sơ bị lỗi cần Ban tuyển sinh rà soát lại:</p>
        <ul style="margin:0 0 10px 20px;padding:0;font-size:13.5px">
          <?php foreach ($dataErrors as $err): ?>
            <li><strong><?= e($err['id']) ?></strong> (<?= e($err['name']) ?>): <?= e($err['error']) ?></li>
          <?php endforeach; ?>
        </ul>
        <div class="alert-actions">
          <a class="btn btn-outline btn-sm" href="<?= e(url('exam/phan-phong.php')) ?>">Đến trang phân phòng thi (UC07) →</a>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- ====================================================================
       BASIC FLOW - BƯỚC 3 & ALTERNATIVE FLOW 3.1:
       Chọn thực hiện cấp SBD và lựa chọn phạm vi (Toàn thành phố / Hội đồng thi)
       Tiêu chí: Chỉ cấp cho thí sinh chưa có SBD (Alternative 3.1)
       ==================================================================== -->
  <section class="sbd-filter-bar">
    <form method="get" action="<?= e(url('candidates/cap-so-bao-danh.php')) ?>" class="sbd-filter-form">
      <?php if (isset($_GET['demo_state'])): ?>
        <input type="hidden" name="demo_state" value="<?= e($_GET['demo_state']) ?>">
      <?php endif; ?>

      <!-- Chọn phạm vi cấp SBD (Toàn thành phố hoặc theo từng Hội đồng thi) -->
      <div class="field">
        <label for="hoiDongSelect">Phạm vi cấp SBD:</label>
        <select id="hoiDongSelect" name="hoiDong">
          <option value="">-- Toàn thành phố (Tất cả hội đồng) --</option>
          <?php foreach ($councils as $h): ?>
            <option value="<?= e($h['maHoiDongThi']) ?>"<?= $council === $h['maHoiDongThi'] ? ' selected' : '' ?>>
              <?= e($h['tenHoiDongThi']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tiêu chí lọc danh sách (Alternative Flow 3.1) -->
      <div class="field">
        <label for="trangThaiSelect">Tiêu chí danh sách:</label>
        <select id="trangThaiSelect" name="trangThai">
          <option value="pending"<?= $state === 'pending' ? ' selected' : '' ?>>
            Chỉ cấp cho thí sinh chưa có SBD (<?= $totalPending ?>)
          </option>
          <option value="issued"<?= $state === 'issued' ? ' selected' : '' ?>>
            Đã có số báo danh (<?= $totalIssued ?>)
          </option>
          <option value="all"<?= $state === 'all' ? ' selected' : '' ?>>
            Tất cả thí sinh hợp lệ (<?= $totalEligible ?>)
          </option>
        </select>
      </div>

      <!-- Ô tìm kiếm nhanh -->
      <div class="field">
        <label for="sbdSearchInput">Tìm kiếm nhanh:</label>
        <input type="search" id="sbdSearchInput" name="q" value="<?= e($searchQuery) ?>" placeholder="Nhập tên, mã thí sinh, SBD, phòng thi...">
      </div>

      <div class="sbd-filter-actions">
        <button type="submit" class="btn btn-primary">Xem danh sách</button>
        <?php if ($council !== '' || $state !== 'pending' || $searchQuery !== ''): ?>
          <a href="<?= e(url('candidates/cap-so-bao-danh.php')) ?>" class="btn btn-outline">Đặt lại</a>
        <?php endif; ?>
        <a class="btn btn-outline" href="<?= e(url('candidates/cap-so-bao-danh.php', array_filter(['hoiDong' => $council ?: null, 'trangThai' => $state, 'q' => $searchQuery ?: null, 'export' => '1']))) ?>" title="Alternative Flow 7.2">
          Xuất CSV
        </a>
      </div>
    </form>
  </section>

  <!-- ====================================================================
       BẢNG DANH SÁCH THÍ SINH
       ==================================================================== -->
  <form id="sbdMainForm" method="post" action="<?= e(url('candidates/cap-so-bao-danh.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="confirm" value="1">
    <input type="hidden" name="hoiDong" value="<?= e($council) ?>">
    <input type="hidden" name="trangThai" value="<?= e($state) ?>">

    <section class="sbd-table-card">
      <div class="table-top">
        <div>
          Hiển thị <strong><?= count($rows) ?></strong> hồ sơ thí sinh
          <?php if ($state === 'pending'): ?>
            (đang chờ cấp SBD)
          <?php elseif ($state === 'issued'): ?>
            (đã được cấp SBD)
          <?php endif; ?>
          · Thứ tự sắp xếp tiếng Việt A–B–C.
        </div>
        <div class="table-actions">
          <label style="font-size:13px;display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="checkbox" id="selectAllCheckbox" onchange="document.querySelectorAll('.cand-checkbox').forEach(cb => cb.checked = this.checked);">
            <span>Chọn tất cả</span>
          </label>
        </div>
      </div>

      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col" style="width:40px;text-align:center">#</th>
              <th scope="col" style="width:50px">STT</th>
              <th scope="col">Mã thí sinh</th>
              <th scope="col">Mã hồ sơ</th>
              <th scope="col">Họ và tên</th>
              <th scope="col">Ngày sinh</th>
              <th scope="col">Số báo danh (SBD)</th>
              <th scope="col">Phòng thi</th>
              <th scope="col">Trường THCS</th>
              <th scope="col">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="10" style="text-align:center;padding:30px;color:var(--muted)">
                  <?php if ($state === 'pending'): ?>
                    🎉 Hoàn tất: Không còn thí sinh hợp lệ nào đang chờ cấp số báo danh trong phạm vi đã chọn.
                  <?php else: ?>
                    Không tìm thấy hồ sơ thí sinh phù hợp với bộ lọc hiện tại.
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $index => $row): ?>
                <?php
                  $hasSBD = !empty($row['soBaoDanh']);
                  $sbdVal = $row['soBaoDanh'] ?? '';
                ?>
                <tr>
                  <td style="text-align:center">
                    <input type="checkbox"
                           name="candidates[]"
                           value="<?= e($row['maThiSinh']) ?>"
                           class="cand-checkbox"
                           aria-label="Chọn thí sinh <?= e($row['maThiSinh']) ?>">
                  </td>
                  <td><?= $index + 1 ?></td>
                  <td><span class="code-tag"><?= e($row['maThiSinh']) ?></span></td>
                  <td><?= e($row['maHoSo']) ?></td>
                  <td><strong><?= e($row['hoTen']) ?></strong></td>
                  <td><?= !empty($row['ngaySinh']) ? e(date('d/m/Y', strtotime($row['ngaySinh']))) : '—' ?></td>
                  <td>
                    <?php if ($hasSBD): ?>
                      <strong style="color:var(--primary-dark);font-family:monospace;font-size:14px"><?= e($sbdVal) ?></strong>
                    <?php else: ?>
                      <span style="color:var(--muted);font-style:italic">Chưa có SBD</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($row['phongThi']) && $row['phongThi'] !== 'Chưa phân phòng'): ?>
                      <span class="code-tag" style="background:#e8f4fd;color:#0277bd;border-color:#b3e5fc"><?= e($row['phongThi']) ?></span>
                    <?php else: ?>
                      <span style="color:#c62828">Chưa phân phòng</span>
                    <?php endif; ?>
                  </td>
                  <td><?= e(school_display_name($row['tenTruongTHCS'] ?? '')) ?></td>
                  <td>
                    <?php if ($hasSBD): ?>
                      <span class="sbd-badge-issued">Đã cấp SBD</span>
                    <?php else: ?>
                      <span class="sbd-badge-pending">Chờ cấp SBD</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ====================================================================
         THANH NÚT HÀNH ĐỘNG (ACTION BAR)
         ==================================================================== -->
    <div class="sbd-actions-footer">
      <div class="left-actions">
        <?php if ($state !== 'issued' && !$registrationBlocked && !$isAllIssued): ?>
          <!-- Basic Flow Bước 3, 4: Cấp SBD cho toàn bộ hồ sơ trong phạm vi -->
          <button type="button"
                  class="btn btn-primary"
                  data-student-dialog-open="modal-confirm-issue"
                  <?= empty($rows) ? ' disabled' : '' ?>>
            <span aria-hidden="true">🔢</span> Cấp số báo danh cho <?= count($rows) ?> thí sinh
          </button>
          <!-- Cấp cho các thí sinh được chọn checkbox -->
          <button type="submit"
                  name="action"
                  value="issue"
                  class="btn btn-soft"
                  onclick="return confirm('Xác nhận cấp số báo danh cho các thí sinh đã chọn?');"
                  <?= empty($rows) ? ' disabled' : '' ?>>
            Cấp SBD cho mục đã chọn
          </button>
        <?php endif; ?>

        <?php if ($state !== 'pending' && $totalIssued > 0): ?>
          <!-- Alternative Flow 7.1: Thu hồi SBD đã chọn -->
          <button type="submit"
                  name="action"
                  value="revoke"
                  class="btn btn-outline"
                  onclick="return confirm('Bạn có chắc chắn muốn thu hồi số báo danh của các thí sinh đã chọn?');"
                  <?= empty($rows) ? ' disabled' : '' ?>>
            Thu hồi SBD đã chọn
          </button>
          <!-- Alternative Flow 7.1: Hủy/Thu hồi toàn bộ SBD (Reset) -->
          <button type="button"
                  class="btn btn-outline"
                  style="color:#c62828;border-color:#ef9a9a"
                  data-student-dialog-open="modal-revoke-all">
            Hủy / Thu hồi toàn bộ SBD (Reset)
          </button>
        <?php endif; ?>
      </div>

      <div class="right-actions">
        <a class="btn btn-outline" href="<?= e(url('candidates/')) ?>">
          ← Danh sách thí sinh
        </a>
        <a class="btn btn-outline" href="<?= e(url('exam/')) ?>">
          Phân hệ Quản lý kỳ thi →
        </a>
      </div>
    </div>

    <!-- ====================================================================
         BASIC FLOW - BƯỚC 4 & 5: HỘP THOẠI XÁC NHẬN CẤP SỐ BÁO DANH
         "Bạn có chắc chắn muốn cấp Số báo danh cho [X] thí sinh? Quá trình này có thể mất vài phút."
         ==================================================================== -->
    <dialog id="modal-confirm-issue" class="student-dialog sbd-confirm-modal" aria-labelledby="confirm-modal-title">
      <header class="admission-modal-header" style="margin-bottom:14px;padding-bottom:12px">
        <div>
          <span class="section-overline">XÁC NHẬN THAO TÁC CẤP SỐ BÁO DANH</span>
          <h2 id="confirm-modal-title" style="margin:4px 0">Yêu cầu xác nhận cấp SBD</h2>
        </div>
        <button type="button" data-student-dialog-close class="dialog-close-btn" aria-label="Đóng">×</button>
      </header>

      <!-- Câu hỏi xác nhận chuẩn Use Case -->
      <p class="confirm-question">
        Bạn có chắc chắn muốn cấp Số báo danh cho <span style="color:#d32f2f"><?= count($rows) ?></span> thí sinh? Quá trình này có thể mất vài phút.
      </p>

      <div class="confirm-specs">
        <dl>
          <dt>Phạm vi áp dụng:</dt>
          <dd><?= $council !== '' ? 'Hội đồng thi đã chọn' : 'Toàn thành phố' ?></dd>
          <dt>Số lượng hồ sơ:</dt>
          <dd><?= count($rows) ?> thí sinh hợp lệ (đã duyệt, đã có phòng thi)</dd>
          <dt>Quy tắc đánh số:</dt>
          <dd>Sắp xếp theo thứ tự tiếng Việt A–B–C (Tên → Họ đệm → Ngày sinh)</dd>
          <dt>Dải số SBD:</dt>
          <dd>Tự động sinh số nối tiếp theo dải số cũ, không đổi SBD đã có</dd>
        </dl>
      </div>

      <p style="font-size:13px;color:var(--muted);margin:0 0 20px;line-height:1.5">
        ℹ Sau khi cấp thành công, số báo danh sẽ được tự động liên kết với Phòng thi và Điểm thi tương ứng để sẵn sàng xuất danh sách phòng thi và tra cứu điểm thi.
      </p>

      <footer style="display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-outline" data-student-dialog-close>
          Hủy bỏ
        </button>
        <!-- Bước 5: Nhấn Đồng ý -->
        <button type="submit" name="action" value="issue_scope" class="btn btn-primary">
          Đồng ý thực hiện cấp SBD
        </button>
      </footer>
    </dialog>

    <!-- ====================================================================
         ALTERNATIVE FLOW 7.1: HỘP THOẠI XÁC NHẬN HỦY / THU HỒI TOÀN BỘ SBD (RESET)
         ==================================================================== -->
    <dialog id="modal-revoke-all" class="student-dialog sbd-confirm-modal" aria-labelledby="revoke-modal-title">
      <header class="admission-modal-header" style="margin-bottom:14px;padding-bottom:12px">
        <div>
          <span class="section-overline" style="color:#c62828">CẢNH BÁO BẢO MẬT NGHIỆP VỤ</span>
          <h2 id="revoke-modal-title" style="margin:4px 0;color:#c62828">Hủy / Thu hồi toàn bộ Số báo danh</h2>
        </div>
        <button type="button" data-student-dialog-close class="dialog-close-btn" aria-label="Đóng">×</button>
      </header>

      <div style="background:#fff8f8;border:1px solid #ffcdd2;padding:14px 18px;border-radius:8px;margin-bottom:18px">
        <p style="margin:0;font-size:14px;color:#b71c1c;line-height:1.6">
          ⚠️ <strong>Chú ý quan trọng:</strong> Hành động này sẽ xóa toàn bộ số báo danh đã tạo trong phạm vi đã chọn và đưa toàn bộ thí sinh về trạng thái <em>"Chưa có SBD"</em>. Chỉ thực hiện khi Ban tuyển sinh phát hiện danh sách thí sinh bị sai lệch cần lập lại từ đầu.
        </p>
      </div>

      <div style="margin-bottom:20px">
        <label style="display:flex;align-items:flex-start;gap:8px;font-size:13.5px;cursor:pointer">
          <input type="checkbox" id="revokeConfirmCheckbox" required onchange="document.getElementById('revokeSubmitBtn').disabled = !this.checked;">
          <span>Tôi xác nhận danh sách bị sai lệch và đồng ý thu hồi toàn bộ số báo danh đã tạo.</span>
        </label>
      </div>

      <footer style="display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-outline" data-student-dialog-close>
          Hủy bỏ
        </button>
        <button type="submit" name="action" value="revoke_all" id="revokeSubmitBtn" class="btn btn-primary" style="background:#d32f2f;border-color:#d32f2f" disabled>
          Xác nhận thu hồi toàn bộ SBD
        </button>
      </footer>
    </dialog>
  </form>
</div>

<?php ui_page_end(); ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__) . '/services/live_data.php';

// Safe fallback variable initialization
$admissionSearch = $admissionSearch ?? (is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 120) : '');
$filterRegion = $filterRegion ?? (is_string($_GET['khu_vuc'] ?? null) ? trim($_GET['khu_vuc']) : 'all');
$filterSchool = $filterSchool ?? (is_string($_GET['truong'] ?? null) ? trim($_GET['truong']) : 'all');
$filterEducation = $filterEducation ?? (is_string($_GET['he_dao_tao'] ?? null) ? trim($_GET['he_dao_tao']) : 'all');
$filterMethod = $filterMethod ?? (is_string($_GET['phuong_thuc'] ?? null) ? trim($_GET['phuong_thuc']) : 'all');
$viewMode = $viewMode ?? ((isset($_GET['view']) && $_GET['view'] === 'table') ? 'table' : 'grid');

// Retrieve all published admission notices (unfiltered base)
$baseNotices = live_student_admission_info('');
$hasAnyAdmissionData = !empty($baseNotices);

// Retrieve active list of notices
if (!isset($admissionNotices) || $admissionNotices === null) {
    $admissionNotices = live_student_admission_info($admissionSearch, [
        'region' => $filterRegion,
        'school' => $filterSchool,
        'education' => $filterEducation,
        'method' => $filterMethod,
    ]);
}

// Build distinct filter options from base dataset
$distinctRegions = [];
$distinctSchools = [];
$distinctEducations = [];
$distinctMethods = [];
$totalSystemQuota = 0;

if ($hasAnyAdmissionData) {
    foreach ($baseNotices as $n) {
        $rKey = $n['maKhuVuc'] ?? $n['tenKhuVuc'];
        if (!isset($distinctRegions[$rKey])) {
            $distinctRegions[$rKey] = [
                'code' => $n['maKhuVuc'] ?? '',
                'name' => $n['tenKhuVuc'] ?? '',
                'districts' => $n['quanHuyen'] ?? '',
            ];
        }
        $sKey = $n['maTruongTHPT'];
        if (!isset($distinctSchools[$sKey])) {
            $distinctSchools[$sKey] = [
                'code' => $sKey,
                'name' => $n['tenTruongTHPT'],
            ];
        }
        if (!empty($n['heDaoTao']) && !in_array($n['heDaoTao'], $distinctEducations, true)) {
            $distinctEducations[] = $n['heDaoTao'];
        }
        if (!empty($n['phuongThucTuyenSinh']) && !in_array($n['phuongThucTuyenSinh'], $distinctMethods, true)) {
            $distinctMethods[] = $n['phuongThucTuyenSinh'];
        }
        $totalSystemQuota += (int)($n['chiTieuTuyenSinh'] ?? 0);
    }
}

// Count visible stats
$visibleCount = is_array($admissionNotices) ? count($admissionNotices) : 0;
$visibleQuota = 0;
if (is_array($admissionNotices)) {
    foreach ($admissionNotices as $row) {
        $visibleQuota += (int)($row['chiTieuTuyenSinh'] ?? 0);
    }
}
$isDemoEmpty = isset($_GET['demo_state']) && $_GET['demo_state'] === 'empty';
?>

<div class="admission-portal-wrapper" data-admission-container data-active-region="<?= e($filterRegion) ?>">
  <!-- Demo Toolbar: Cho phép kiểm thử nhanh tất cả các luồng theo Use Case -->
  <aside class="demo-flow-toolbar" aria-label="Thanh công cụ kiểm thử nhanh Use Case">
    <div class="toolbar-label">
      <span aria-hidden="true">⚙</span>
      <span>Kiểm thử Use Case UC01:</span>
    </div>
    <div class="toolbar-actions">
      <a class="toolbar-chip<?= (!$isDemoEmpty && $admissionSearch === '' && $filterRegion === 'all') ? ' is-active' : '' ?>"
         href="<?= e(url('admission-info/')) ?>" title="Luồng cơ bản: Xem danh mục thông tin tuyển sinh">
        Basic Flow (Toàn bộ danh mục)
      </a>
      <a class="toolbar-chip<?= ($admissionSearch === 'Sao Mai') ? ' is-active' : '' ?>"
         href="<?= e(url('admission-info/?q=Sao+Mai')) ?>" title="Luồng thay thế: Tìm kiếm nhanh theo tên trường">
        Alternative Flow 3.1 (Tìm kiếm "Sao Mai")
      </a>
      <a class="toolbar-chip<?= ($filterRegion === 'KV01') ? ' is-active' : '' ?>"
         href="<?= e(url('admission-info/?khu_vuc=KV01')) ?>" title="Chọn xem chi tiết theo Khu vực 1">
        Basic Flow Bước 3 (Lọc Khu vực 1)
      </a>
      <a class="toolbar-chip<?= ($admissionSearch === 'KhongTimThayTruongNay999') ? ' is-active' : '' ?>"
         href="<?= e(url('admission-info/?q=KhongTimThayTruongNay999')) ?>" title="Luồng ngoại lệ 3.1: Không tìm thấy kết quả">
        Exception Flow 3.1 (Không có kết quả)
      </a>
      <a class="toolbar-chip<?= $isDemoEmpty ? ' is-active' : '' ?>"
         href="<?= e(url('admission-info/?demo_state=empty')) ?>" title="Luồng ngoại lệ 2.1: Chưa có thông tin tuyển sinh">
        Exception Flow 2.1 (Chưa có thông tin)
      </a>
    </div>
  </aside>

  <!-- Breadcrumb Navigation -->
  <nav class="public-breadcrumb" aria-label="Đường dẫn">
    <a href="<?= e(url()) ?>">Trang chủ</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Xem thông tin tuyển sinh lớp 10</span>
  </nav>

  <!-- Tiêu đề trang & Thống kê tóm tắt (Basic Flow - Bước 1) -->
  <header class="admission-header-card">
    <div class="header-top">
      <div>
        <span class="section-overline">CỔNG THÔNG TIN TUYỂN SINH · NĂM HỌC 2026–2027</span>
        <h1>Xem thông tin tuyển sinh vào lớp 10</h1>
        <p class="header-desc">
          Danh mục thông tin tuyển sinh chính thức dành cho học sinh: chỉ tiêu tuyển sinh, danh sách trường THPT, hệ đào tạo, phương thức tuyển sinh và học phí.
        </p>
      </div>
      <div>
        <span class="admission-header-badge">Đã công bố chính thức</span>
      </div>
    </div>

    <!-- 4 chỉ số tổng quan -->
    <div class="admission-quick-stats">
      <div class="admission-stat-box">
        <span class="stat-label">Trường THPT công bố</span>
        <span class="stat-value" data-admission-count><?= count($distinctSchools) ?> trường</span>
        <span class="stat-sub">Công lập trên toàn địa bàn</span>
      </div>
      <div class="admission-stat-box">
        <span class="stat-label">Tổng chỉ tiêu tuyển sinh</span>
        <span class="stat-value" data-admission-total-quota><?= number_format($totalSystemQuota, 0, ',', '.') ?></span>
        <span class="stat-sub">Học sinh lớp 10 năm học 2026</span>
      </div>
      <div class="admission-stat-box">
        <span class="stat-label">Khu vực tuyển sinh</span>
        <span class="stat-value"><?= count($distinctRegions) ?> khu vực</span>
        <span class="stat-sub">Phân tuyến theo quận/huyện</span>
      </div>
      <div class="admission-stat-box">
        <span class="stat-label">Năm học áp dụng</span>
        <span class="stat-value">2026–2027</span>
        <span class="stat-sub">Kỳ tuyển sinh lớp 10 chung</span>
      </div>
    </div>
  </header>

  <?php if (!$hasAnyAdmissionData || $isDemoEmpty): ?>
    <!-- ====================================================================
         EXCEPTION FLOW 2.1: Chưa có thông tin tuyển sinh / Chưa tới kỳ công bố
         1. Thông báo “Chưa có thông tin tuyển sinh”.
         2. Kết thúc Use Case.
         ==================================================================== -->
    <section class="admission-exception-panel admission-exception-empty" role="alert" aria-labelledby="empty-admission-title">
      <div class="exception-icon" aria-hidden="true">📢</div>
      <div class="exception-content">
        <h2 id="empty-admission-title">Chưa có thông tin tuyển sinh</h2>
        <p>
          Hiện tại hệ thống chưa tới kỳ công bố thông tin tuyển sinh vào lớp 10 cho năm học này hoặc thông tin chưa được công bố chính thức.
        </p>
        <p class="exception-hint">
          Thông tin chi tiết về chỉ tiêu tuyển sinh từng trường THPT, phương thức tuyển sinh và mức học phí sẽ được Ban Tuyển sinh công bố theo đúng kế hoạch của Sở Giáo dục và Đào tạo. Học sinh và phụ huynh vui lòng theo dõi các thông báo tiếp theo.
        </p>
        <div class="exception-actions">
          <a class="btn btn-primary" href="<?= e(url()) ?>">← Quay lại Trang chủ</a>
          <a class="btn btn-outline" href="<?= e(url('admission-info/huong-dan.php')) ?>">Xem quy trình & hướng dẫn</a>
          <?php if ($isDemoEmpty): ?>
            <a class="btn btn-soft" href="<?= e(url('admission-info/')) ?>">Bật lại dữ liệu tuyển sinh đã công bố</a>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php else: ?>

    <!-- ====================================================================
         ALTERNATIVE FLOW 3.1: Tìm kiếm thông tin nhanh
         1. Nhập từ khóa cần tìm vào thanh tìm kiếm (tên trường, mã trường, quận/huyện)
         2. Hệ thống lọc và hiển thị danh sách các kết quả khớp với từ khóa
         ==================================================================== -->
    <section class="admission-search-bar" aria-label="Tìm kiếm thông tin tuyển sinh nhanh">
      <form method="get" action="<?= e(url('admission-info/')) ?>">
        <?php if ($filterRegion !== 'all'): ?><input type="hidden" name="khu_vuc" value="<?= e($filterRegion) ?>"><?php endif; ?>
        <?php if ($filterEducation !== 'all'): ?><input type="hidden" name="he_dao_tao" value="<?= e($filterEducation) ?>"><?php endif; ?>
        <?php if ($filterMethod !== 'all'): ?><input type="hidden" name="phuong_thuc" value="<?= e($filterMethod) ?>"><?php endif; ?>
        <?php if ($viewMode !== 'grid'): ?><input type="hidden" name="view" value="<?= e($viewMode) ?>"><?php endif; ?>

        <div class="search-row">
          <div class="search-input-wrap">
            <span class="search-icon" aria-hidden="true">⌕</span>
            <input type="search"
                   id="admission-search-input"
                   name="q"
                   value="<?= e($admissionSearch) ?>"
                   placeholder="Tìm kiếm nhanh: nhập tên trường, mã trường (vd: THPT_1), quận/huyện (vd: Quận 1, Gò Vấp)..."
                   aria-label="Tìm kiếm thông tin tuyển sinh nhanh"
                   data-admission-live-search
                   autocomplete="off">
            <?php if ($admissionSearch !== ''): ?>
              <button type="button" class="clear-search-btn" data-reset-search-btn aria-label="Xóa từ khóa">×</button>
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-primary">Tìm kiếm</button>
          <?php if ($admissionSearch !== '' || $filterRegion !== 'all' || $filterSchool !== 'all'): ?>
            <a href="<?= e(url('admission-info/')) ?>" class="btn btn-outline">Xem tất cả</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <!-- ====================================================================
         BASIC FLOW - BƯỚC 3: Chọn loại thông tin trường THPT hoặc khu vực cần xem
         Thanh Tabs lọc theo Khu vực & Dropdowns lọc chi tiết
         ==================================================================== -->
    <section class="admission-filter-section" aria-label="Bộ lọc thông tin trường THPT và khu vực">
      <!-- Tabs chọn Khu vực -->
      <div class="admission-region-tabs" role="tablist" aria-label="Lọc theo khu vực tuyển sinh">
        <a href="<?= e(url('admission-info/', array_filter(['q' => $admissionSearch ?: null, 'view' => $viewMode !== 'grid' ? $viewMode : null]))) ?>"
           class="region-chip<?= $filterRegion === 'all' ? ' active' : '' ?>"
           data-region-filter-btn="all"
           role="tab"
           aria-pressed="<?= $filterRegion === 'all' ? 'true' : 'false' ?>">
          Tất cả khu vực (<?= count($distinctSchools) ?> trường)
        </a>
        <?php foreach ($distinctRegions as $rCode => $rInfo): ?>
          <?php
            $rActive = ($filterRegion === $rCode || $filterRegion === $rInfo['name']);
            $rUrl = url('admission-info/', array_filter([
                'khu_vuc' => $rCode,
                'q' => $admissionSearch ?: null,
                'view' => $viewMode !== 'grid' ? $viewMode : null,
            ]));
          ?>
          <a href="<?= e($rUrl) ?>"
             class="region-chip<?= $rActive ? ' active' : '' ?>"
             data-region-filter-btn="<?= e($rCode) ?>"
             role="tab"
             aria-pressed="<?= $rActive ? 'true' : 'false' ?>">
            <?= e($rInfo['name']) ?> (<?= e($rInfo['districts']) ?>)
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Dropdown lọc chi tiết & Chuyển đổi chế độ xem Grid/Table -->
      <div class="admission-filter-row">
        <div class="admission-filter-group">
          <label for="school-select">Chọn trường THPT:</label>
          <select id="school-select" data-school-select-filter onchange="location = this.value;">
            <option value="<?= e(url('admission-info/', array_filter(['khu_vuc' => $filterRegion !== 'all' ? $filterRegion : null, 'q' => $admissionSearch ?: null, 'view' => $viewMode !== 'grid' ? $viewMode : null]))) ?>">-- Tất cả trường THPT --</option>
            <?php foreach ($distinctSchools as $sCode => $sInfo): ?>
              <?php
                $sSelected = ($filterSchool === $sCode);
                $sUrl = url('admission-info/', array_filter([
                    'truong' => $sCode,
                    'khu_vuc' => $filterRegion !== 'all' ? $filterRegion : null,
                    'q' => $admissionSearch ?: null,
                    'view' => $viewMode !== 'grid' ? $viewMode : null,
                ]));
              ?>
              <option value="<?= e($sUrl) ?>"<?= $sSelected ? ' selected' : '' ?>>
                <?= e($sInfo['name']) ?> (<?= e($sCode) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="admission-view-mode" aria-label="Chế độ hiển thị danh mục">
          <span style="font-size:13px;color:var(--muted);margin-right:6px">Chế độ xem:</span>
          <a href="<?= e(url('admission-info/', array_filter(['q' => $admissionSearch ?: null, 'khu_vuc' => $filterRegion !== 'all' ? $filterRegion : null, 'truong' => $filterSchool !== 'all' ? $filterSchool : null]))) ?>"
             class="<?= $viewMode === 'grid' ? 'is-active' : '' ?>"
             title="Hiển thị dạng thẻ (Card View)" aria-label="Xem dạng thẻ">
            ▦
          </a>
          <a href="<?= e(url('admission-info/', array_filter(['view' => 'table', 'q' => $admissionSearch ?: null, 'khu_vuc' => $filterRegion !== 'all' ? $filterRegion : null, 'truong' => $filterSchool !== 'all' ? $filterSchool : null]))) ?>"
             class="<?= $viewMode === 'table' ? 'is-active' : '' ?>"
             title="Hiển thị dạng bảng (Table View)" aria-label="Xem dạng bảng">
            ▥
          </a>
        </div>
      </div>
    </section>

    <!-- Kết quả tóm tắt -->
    <div class="admission-results-summary">
      <span>
        Hiển thị <strong data-admission-count><?= $visibleCount ?> trường THPT</strong>
        với tổng chỉ tiêu <strong data-admission-total-quota><?= number_format($visibleQuota, 0, ',', '.') ?></strong> học sinh.
      </span>
      <?php if ($admissionSearch !== ''): ?>
        <span>Từ khóa tìm kiếm: <strong>«<?= e($admissionSearch) ?>»</strong></span>
      <?php endif; ?>
    </div>

    <!-- ====================================================================
         EXCEPTION FLOW 3.1: Không tìm thấy kết quả
         1. Từ khóa học sinh nhập không khớp với bất kỳ trường THPT hoặc danh mục nào
         2. Hệ thống thông báo "Không tìm thấy thông tin phù hợp với từ khóa"
         3. Quay lại bước 3.1 để Học sinh nhập lại từ khóa
         ==================================================================== -->
    <?php if ($visibleCount === 0): ?>
      <section class="admission-exception-panel admission-exception-notfound" role="alert" aria-labelledby="notfound-title">
        <div class="exception-icon" aria-hidden="true">🔍</div>
        <div class="exception-content">
          <h2 id="notfound-title">Không tìm thấy thông tin phù hợp với từ khóa</h2>
          <p>
            Không tìm thấy thông tin trường THPT hoặc danh mục nào phù hợp với từ khóa
            <strong>«<?= e($admissionSearch) ?>»</strong><?= $filterRegion !== 'all' ? ' trong khu vực đã chọn' : '' ?>.
          </p>
          <div class="notfound-suggestions">
            <strong>Gợi ý tra cứu nhanh cho học sinh:</strong>
            <ul>
              <li>Kiểm tra lại chính tả của từ khóa vừa nhập.</li>
              <li>Thử tìm kiếm với tên trường phổ thông ngắn gọn (ví dụ: <em>Sao Mai</em>, <em>Bình Minh</em>, <em>Hòa An</em>, <em>Ánh Dương</em>, <em>Trần Quốc Khải</em>).</li>
              <li>Tìm theo mã trường (ví dụ: <em>THPT_1</em>, <em>THPT_2</em>, <em>THPT_3</em>...).</li>
              <li>Tìm theo quận/huyện (ví dụ: <em>Quận 1</em>, <em>Quận 3</em>, <em>Quận 5</em>, <em>Gò Vấp</em>...).</li>
              <li>Chọn xem theo từng <strong>Khu vực</strong> ở thanh tab phía trên.</li>
            </ul>
          </div>
          <div class="exception-actions">
            <button type="button" class="btn btn-primary" data-reset-search-btn>Nhập lại từ khóa</button>
            <a class="btn btn-outline" href="<?= e(url('admission-info/')) ?>">Xem tất cả thông tin tuyển sinh</a>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- Hộp thông báo lỗi không tìm thấy khi live filter bằng JavaScript -->
    <section id="admission-notfound-client" class="admission-exception-panel admission-exception-notfound" role="alert" hidden>
      <div class="exception-icon" aria-hidden="true">🔍</div>
      <div class="exception-content">
        <h2>Không tìm thấy thông tin phù hợp với từ khóa</h2>
        <p>
          Không tìm thấy thông tin phù hợp với từ khóa <strong data-search-keyword-display></strong>.
        </p>
        <div class="notfound-suggestions">
          <strong>Gợi ý tra cứu:</strong>
          <ul>
            <li>Kiểm tra lại chính tả từ khóa.</li>
            <li>Thử tìm theo tên trường (vd: Sao Mai, Bình Minh) hoặc mã trường (vd: THPT_1).</li>
            <li>Nhấn "Nhập lại từ khóa" bên dưới để xóa và thử tìm từ khóa khác.</li>
          </ul>
        </div>
        <div class="exception-actions">
          <button type="button" class="btn btn-primary" data-reset-search-btn>Nhập lại từ khóa</button>
          <a class="btn btn-outline" href="<?= e(url('admission-info/')) ?>">Xem tất cả thông tin tuyển sinh</a>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         BASIC FLOW - BƯỚC 2: Hiển thị danh mục thông tin tuyển sinh
         (chỉ tiêu, trường THPT, hệ đào tạo, phương thức tuyển sinh, học phí)
         ==================================================================== -->
    <?php if ($visibleCount > 0 && $viewMode === 'grid'): ?>
      <!-- Chế độ xem dạng Thẻ (Grid View) -->
      <section class="admission-grid" data-admission-grid aria-label="Danh mục thông tin tuyển sinh dạng thẻ">
        <?php foreach ($admissionNotices as $notice): ?>
          <?php
            $schoolCode = $notice['maTruongTHPT'] ?? '';
            $schoolName = $notice['tenTruongTHPT'] ?? '';
            $regionCode = $notice['maKhuVuc'] ?? '';
            $regionName = $notice['tenKhuVuc'] ?? '';
            $districts = $notice['quanHuyen'] ?? '';
            $quota = (int)($notice['chiTieuTuyenSinh'] ?? 0);
            $education = $notice['heDaoTao'] ?? 'Công lập';
            $method = $notice['phuongThucTuyenSinh'] ?? 'Thi tuyển';
            $fee = (float)($notice['hocPhi'] ?? 0);
            $dialogId = 'admission-modal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $schoolCode);
          ?>
          <article class="admission-card"
                   data-admission-item
                   data-school="<?= e($schoolCode) ?>"
                   data-region="<?= e($regionCode) ?>"
                   data-quota="<?= $quota ?>">
            <div class="admission-card-head">
              <span class="card-code-tag"><?= e($schoolCode) ?></span>
              <span class="card-region-tag"><?= e($regionName) ?></span>
            </div>

            <h2><?= e(school_display_name($schoolName)) ?></h2>
            <p class="card-district">
              <span aria-hidden="true">📍</span> <?= e($districts) ?>
            </p>

            <!-- Chỉ tiêu tuyển sinh nổi bật -->
            <div class="admission-card-quota">
              <span class="quota-num"><?= $quota ?></span>
              <span class="quota-label">Chỉ tiêu tuyển sinh lớp 10</span>
            </div>

            <!-- Danh mục thông số chính theo đặc tả -->
            <dl class="admission-card-specs">
              <div class="admission-spec-item">
                <dt>Hệ đào tạo</dt>
                <dd><?= e($education) ?></dd>
              </div>
              <div class="admission-spec-item">
                <dt>Phương thức tuyển sinh</dt>
                <dd><?= e($method) ?></dd>
              </div>
              <div class="admission-spec-item">
                <dt>Học phí áp dụng</dt>
                <dd><?= $fee > 0 ? e(number_format($fee, 0, ',', '.')) . ' đồng' : 'Theo quy định công lập' ?></dd>
              </div>
              <div class="admission-spec-item">
                <dt>Đợt tuyển sinh</dt>
                <dd><?= e($notice['tenDot'] ?? 'Tuyển sinh 10') ?></dd>
              </div>
            </dl>

            <!-- Xem trước nội dung -->
            <div class="admission-card-preview">
              <p><?= e(mb_strimwidth(strip_tags((string)($notice['noiDungChiTiet'] ?? '')), 0, 140, '…')) ?></p>
            </div>

            <!-- Basic Flow Bước 3, 4: Chọn xem chi tiết -->
            <div class="admission-card-footer">
              <button type="button"
                      class="btn btn-outline"
                      data-student-dialog-open="<?= e($dialogId) ?>"
                      aria-haspopup="dialog"
                      aria-controls="<?= e($dialogId) ?>">
                Xem chi tiết thông tin tuyển sinh →
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

    <?php if ($visibleCount > 0 && $viewMode === 'table'): ?>
      <!-- Chế độ xem dạng Bảng (Table View) -->
      <section class="admission-table-container table-scroll" data-admission-table-wrap aria-label="Bảng danh mục thông tin tuyển sinh">
        <table class="admission-table">
          <thead>
            <tr>
              <th scope="col">Mã trường</th>
              <th scope="col">Tên trường THPT</th>
              <th scope="col">Khu vực / Địa bàn</th>
              <th scope="col">Chỉ tiêu</th>
              <th scope="col">Hệ đào tạo</th>
              <th scope="col">Phương thức tuyển sinh</th>
              <th scope="col">Học phí</th>
              <th scope="col" style="text-align:center">Nội dung chi tiết</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($admissionNotices as $notice): ?>
              <?php
                $schoolCode = $notice['maTruongTHPT'] ?? '';
                $schoolName = $notice['tenTruongTHPT'] ?? '';
                $regionCode = $notice['maKhuVuc'] ?? '';
                $regionName = $notice['tenKhuVuc'] ?? '';
                $districts = $notice['quanHuyen'] ?? '';
                $quota = (int)($notice['chiTieuTuyenSinh'] ?? 0);
                $education = $notice['heDaoTao'] ?? 'Công lập';
                $method = $notice['phuongThucTuyenSinh'] ?? 'Thi tuyển';
                $fee = (float)($notice['hocPhi'] ?? 0);
                $dialogId = 'admission-modal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $schoolCode);
              ?>
              <tr data-admission-item
                  data-school="<?= e($schoolCode) ?>"
                  data-region="<?= e($regionCode) ?>"
                  data-quota="<?= $quota ?>">
                <td><span class="code-tag"><?= e($schoolCode) ?></span></td>
                <th scope="row"><strong><?= e(school_display_name($schoolName)) ?></strong></th>
                <td><?= e($regionName) ?><br><small style="color:var(--muted)"><?= e($districts) ?></small></td>
                <td><span class="quota-highlight"><?= $quota ?></span> <small>học sinh</small></td>
                <td><?= e($education) ?></td>
                <td><?= e($method) ?></td>
                <td><?= $fee > 0 ? e(number_format($fee, 0, ',', '.')) . ' đ' : 'Theo quy định' ?></td>
                <td style="text-align:center">
                  <button type="button"
                          class="btn btn-soft btn-sm"
                          data-student-dialog-open="<?= e($dialogId) ?>"
                          aria-label="Xem chi tiết tuyển sinh <?= e($schoolName) ?>">
                    Xem chi tiết
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php endif; ?>

    <!-- ====================================================================
         BASIC FLOW - BƯỚC 4 & 5: Truy xuất và hiển thị nội dung chi tiết
         Hộp thoại Modal chi tiết cho từng trường THPT đã chọn
         ==================================================================== -->
    <?php foreach ($baseNotices as $notice): ?>
      <?php
        $schoolCode = $notice['maTruongTHPT'] ?? '';
        $schoolName = $notice['tenTruongTHPT'] ?? '';
        $regionName = $notice['tenKhuVuc'] ?? '';
        $districts = $notice['quanHuyen'] ?? '';
        $quota = (int)($notice['chiTieuTuyenSinh'] ?? 0);
        $education = $notice['heDaoTao'] ?? 'Công lập';
        $method = $notice['phuongThucTuyenSinh'] ?? 'Thi tuyển';
        $fee = (float)($notice['hocPhi'] ?? 0);
        $dialogId = 'admission-modal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $schoolCode);
      ?>
      <dialog id="<?= e($dialogId) ?>" class="student-dialog student-admission-modal" aria-labelledby="<?= e($dialogId) ?>-title">
        <header class="admission-modal-header">
          <div>
            <span class="section-overline">THÔNG TIN TUYỂN SINH CHI TIẾT</span>
            <h2 id="<?= e($dialogId) ?>-title"><?= e(school_display_name($schoolName)) ?></h2>
            <div class="admission-modal-meta">
              <span class="meta-pill">Mã trường: <?= e($schoolCode) ?></span>
              <span class="meta-pill"><?= e($regionName) ?> · <?= e($districts) ?></span>
              <span class="meta-tag">Đã công bố chính thức</span>
            </div>
          </div>
          <button type="button"
                  data-student-dialog-close
                  aria-label="Đóng thông tin chi tiết <?= e($schoolName) ?>"
                  class="dialog-close-btn"
                  autofocus>×</button>
        </header>

        <!-- 4 chỉ số chính -->
        <div class="admission-modal-stat-grid">
          <div class="admission-modal-stat-box">
            <div class="box-label">Chỉ tiêu tuyển sinh</div>
            <div class="box-value"><?= $quota ?></div>
            <div class="box-unit">học sinh lớp 10</div>
          </div>
          <div class="admission-modal-stat-box">
            <div class="box-label">Hệ đào tạo</div>
            <div class="box-value" style="font-size:16px"><?= e($education) ?></div>
            <div class="box-unit">theo khung chuẩn</div>
          </div>
          <div class="admission-modal-stat-box">
            <div class="box-label">Phương thức</div>
            <div class="box-value" style="font-size:16px"><?= e($method) ?></div>
            <div class="box-unit">3 môn thi tuyển</div>
          </div>
          <div class="admission-modal-stat-box">
            <div class="box-label">Học phí</div>
            <div class="box-value" style="font-size:16px"><?= $fee > 0 ? e(number_format($fee, 0, ',', '.')) . ' đ' : 'Quy định NN' ?></div>
            <div class="box-unit">áp dụng công lập</div>
          </div>
        </div>

        <!-- Khối nội dung chi tiết tuyển sinh -->
        <section class="admission-modal-section">
          <h3><span aria-hidden="true">📄</span> Kế hoạch & Nội dung tuyển sinh chi tiết</h3>
          <div class="detail-content">
            <?= nl2br(e((string)($notice['noiDungChiTiet'] ?? 'Chưa có nội dung văn bản chi tiết kèm theo.'))) ?>
          </div>
        </section>

        <!-- Thông tin quy chế tuyển sinh tham khảo -->
        <section class="admission-modal-section">
          <h3><span aria-hidden="true">ℹ</span> Hướng dẫn đăng ký nguyện vọng</h3>
          <p style="font-size:14px;color:var(--muted);margin:0 0 10px;line-height:1.6">
            Học sinh được đăng ký tối đa 3 nguyện vọng vào các trường THPT công lập theo thứ tự ưu tiên (NV1, NV2, NV3). Các nguyện vọng được xét độc lập theo điểm xét tuyển và chỉ tiêu tuyển sinh đã công bố của từng trường.
          </p>
        </section>

        <!-- Nút hành động tương tác -->
        <footer class="admission-modal-footer">
          <button type="button" class="btn btn-outline" data-student-print>
            <span aria-hidden="true">🖨</span> In thông tin này
          </button>
          <div style="display:flex;gap:10px">
            <a href="<?= e(url('aspirations/')) ?>" class="btn btn-primary">
              Đăng ký nguyện vọng →
            </a>
            <button type="button" class="btn btn-soft" data-student-dialog-close>
              Đóng lại
            </button>
          </div>
        </footer>
      </dialog>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

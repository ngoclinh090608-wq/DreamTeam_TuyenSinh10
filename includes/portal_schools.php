<?php
/** Read-only presentation of the same school list used by the homepage. */
require_once dirname(__DIR__) . '/services/live_data.php';
$school_rows = live_home_schools();
if ($school_rows === null) {
    echo '<div class="notice notice-blue" role="alert">Không tải được chỉ tiêu từ cơ sở dữ liệu. Hãy kiểm tra kết nối và cấu hình database cục bộ.</div>';
    return;
}
$public_schools = array_map(fn($r) => ['code' => $r[0], 'name' => $r[1], 'district' => $r[2], 'quota' => $r[3]], $school_rows);
$school_regions = array_values(array_unique(array_column($public_schools, 'district')));
?>
<div class="school-directory" data-school-directory data-schools-api="<?= e(url('api/v1/schools')) ?>">
 <div class="school-filter">
  <div class="filter-tabs" aria-label="Lọc trường theo khu vực">
   <button class="chip active" type="button" data-school-filter="all" aria-pressed="true">Tất cả</button>
   <?php foreach ($school_regions as $region): ?><button class="chip" type="button" data-school-filter="<?= e($region) ?>" aria-pressed="false"><?= e($region) ?></button><?php endforeach; ?>
  </div>
  <label class="search-input"><span aria-hidden="true">⌕</span><input type="search" placeholder="Nhập tên hoặc mã trường…" aria-label="Tìm trường THPT" data-school-search></label>
 </div>
 <div class="table-scroll" role="region" aria-label="Danh sách trường THPT và chỉ tiêu" tabindex="0">
  <table class="data-table school-table"><thead><tr><th scope="col">Mã trường</th><th scope="col">Tên trường THPT</th><th scope="col">Khu vực</th><th scope="col">Chỉ tiêu</th></tr></thead><tbody data-school-body>
   <?php foreach ($public_schools as $school): ?>
    <tr data-school-item data-region="<?= e($school['district']) ?>"><td><span class="code-tag"><?= e($school['code']) ?></span></td><th scope="row"><?= e(school_display_name($school['name'])) ?></th><td><?= e($school['district']) ?></td><td><?= e($school['quota']) ?> <small>học sinh</small></td></tr>
   <?php endforeach; ?>
  </tbody></table>
 </div>
 <p class="empty-state" id="school-empty" role="status" hidden>Không có trường phù hợp. Hãy thử tên hoặc khu vực khác.</p>
 <div class="directory-note"><span data-school-count aria-live="polite"><?= count($public_schools) ?> trường</span><span data-school-source>Dữ liệu PHP dự phòng · đang đồng bộ REST API</span></div>
</div>

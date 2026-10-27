<?php
declare(strict_types=1);
/** Trang chính module Công bố kết quả: UC15 (điểm thi) và UC22 (kết quả tuyển sinh). */
require_once dirname(__DIR__) . '/includes/grading_ui.php';

grading_page_start('publication', 'Công bố kết quả', 'Công bố điểm & trúng tuyển', 'Công bố điểm thi theo kỳ thi, môn thi và công bố kết quả tuyển sinh.', 'UC15 · UC22');
grading_tabs('publication', '');
?>
<div class="grading-cards">
  <a class="panel" href="<?= e(url('publication/diem-thi.php')) ?>"><span class="quick-icon">◉</span><strong>Công bố điểm thi</strong><p>Chọn kỳ thi, môn thi, kiểm tra danh sách kết quả và công bố để học sinh tra cứu.</p><span class="uc-tag">UC15</span></a>
  <a class="panel" href="<?= e(url('publication/tuyen-sinh.php')) ?>"><span class="quick-icon">◇</span><strong>Công bố kết quả tuyển sinh</strong><p>Duyệt và công bố danh sách trúng tuyển.</p><span class="uc-tag">UC22</span></a>
</div>
<?php grading_page_end();

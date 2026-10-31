<?php
declare(strict_types=1);
/** Trang chính module Chấm thi & điểm: dẫn tới UC12, UC13, UC14. */
require_once dirname(__DIR__) . '/includes/grading_ui.php';
$nguoiDung = require_role(['HD_CHAM_THI']);   // UC12–UC14 – Hội đồng chấm thi

ui_page_start('grading', 'Kết quả chấm thi', 'Chấm thi · Sửa điểm · Thống kê', 'Tra cứu lịch sử chỉnh sửa, cập nhật kết quả chấm và thống kê điểm theo kỳ thi, môn thi.', 'UC12 · UC13 · UC14');
grading_tabs('grading', '');
?>
<div class="ui-cards">
  <a class="panel" href="<?= e(url('grading/nhap-diem.php')) ?>"><span class="quick-icon">✍</span><strong>Nhập điểm thi</strong><p>Chọn kỳ thi, môn thi và nhập điểm cho các bài thi theo mã phách (ẩn danh thí sinh).</p><span class="uc-tag">UC05</span></a>
  <a class="panel" href="<?= e(url('grading/lich-su.php')) ?>"><span class="quick-icon">↺</span><strong>Xem lịch sử chỉnh sửa điểm</strong><p>Chọn kỳ thi, môn thi, nhập mã phách để xem điểm hiện tại và các lần sửa điểm.</p><span class="uc-tag">UC12</span></a>
  <a class="panel" href="<?= e(url('grading/cap-nhat.php')) ?>"><span class="quick-icon">✎</span><strong>Cập nhật kết quả chấm thi</strong><p>Nhập mã phách, sửa điểm kèm lý do. Hệ thống tự ghi lịch sử chỉnh sửa.</p><span class="uc-tag">UC13</span></a>
  <a class="panel" href="<?= e(url('grading/thong-ke.php')) ?>"><span class="quick-icon">▥</span><strong>Thống kê kết quả chấm thi</strong><p>Tổng số bài đã chấm, điểm trung bình, cao nhất, thấp nhất theo môn.</p><span class="uc-tag">UC14</span></a>
</div>
<?php ui_page_end();

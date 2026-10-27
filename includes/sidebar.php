<?php $nav_sections = [
  'TỔNG QUAN' => [ ['admin','Bảng điều khiển','▦'] ],
  'TUYỂN SINH' => [ ['candidates','Thí sinh','♙'],['schools','Trường học','▤'],['admission-info','Thông tin tuyển sinh','▧'],['aspirations','Nguyện vọng','✧'],['results','Tra cứu kết quả','⌕'] ],
  'TỔ CHỨC THI' => [ ['exam','Kỳ thi & phòng thi','▣'],['councils','Hội đồng thi','♧'],['staff','Cán bộ','♙'],['assignments','Phân công nhiệm vụ','⇆'],['papers','Bài thi & số phách','▥'],['grading','Chấm thi & điểm','✓'] ],
  'KẾT QUẢ' => [ ['appeals','Phúc khảo','↺'],['admission','Xét tuyển','◇'],['enrollment','Nhập học','☑'],['publication','Công bố kết quả','◉'],['statistics','Thống kê','▥'] ],
]; ?>
<aside class="sidebar" id="sidebar">
 <a class="sidebar-brand" href="<?= e(url('admin/')) ?>"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt=""><span><strong>DREAM TEAM</strong><small>Admission Portal</small></span></a>
 <div class="sidebar-group-list">
 <?php foreach($nav_sections as $group => $items): ?>
  <section class="sidebar-section"><h2><?= e($group) ?></h2>
  <?php foreach($items as [$key,$label,$symbol]): ?>
   <a class="side-link<?= ($active ?? '') === $key ? ' is-active' : '' ?>" href="<?= e(url($key . '/')) ?>"><span class="side-symbol"><?= e($symbol) ?></span><span><?= e($label) ?></span><?= ($active ?? '') === $key ? '<span class="side-active-dot"></span>' : '' ?></a>
  <?php endforeach; ?>
  </section>
 <?php endforeach; ?>
 </div>
 <div class="sidebar-bottom"><a class="site-back" href="<?= e(url()) ?>">← Về trang chủ</a></div>
</aside>

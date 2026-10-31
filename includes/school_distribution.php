<?php
/** Read-only distribution from the same connection as the dashboard totals. */
$schoolTotal = (int)$liveStats['thiSinh'];
?>
<section class="panel" aria-label="Số học sinh theo trường THCS">
 <div class="panel-heading"><div><h2>Số học sinh theo trường THCS</h2><p>Trường THCS của học sinh · Thanh biểu diễn tỷ lệ trên tổng <?= $schoolTotal ?> học sinh trong database đang kết nối.</p></div></div>
 <div class="bars">
 <?php foreach ($liveStats['theoTHCS'] as [$schoolName, $studentCount]):
     $share = $schoolTotal > 0 ? min(100, max(0, round((int)$studentCount / $schoolTotal * 100, 2))) : 0;
 ?>
 <div><span><?= e($schoolName) ?></span><div><i style="width:<?= $share ?>%"></i></div><strong><?= (int)$studentCount ?> học sinh</strong></div>
 <?php endforeach; ?>
 <?php if (!$liveStats['theoTHCS']): ?><p>Chưa có học sinh để thống kê.</p><?php endif; ?>
 </div>
</section>

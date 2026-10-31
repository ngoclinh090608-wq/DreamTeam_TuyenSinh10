<dialog id="student-score-dialog" class="student-dialog" aria-labelledby="student-score-dialog-title">
 <header class="student-dialog-heading"><h2 id="student-score-dialog-title">Kết quả thi</h2><button type="button" aria-label="Đóng kết quả thi" data-student-dialog-close autofocus>×</button></header>
 <?php if (!$studentDashboard['scores']): ?>
 <p class="student-dialog-empty">Điểm thi của bạn chưa được công bố trên hệ thống.</p>
 <?php else: ?>
 <p class="student-dialog-overline">Điểm thi đã công bố</p>
 <div class="student-score-cards"><?php foreach ($studentDashboard['scores'] as $score): ?>
  <article class="student-score-card"><div><h3><?= e($score['tenMonThi']) ?></h3><p><?= e(catalog_display_name($score['tenKyThi'])) ?></p></div><strong><?= e(number_format((float)$score['diemThi'],2)) ?></strong></article>
 <?php endforeach; ?></div>
 <?php endif; ?>
</dialog>
<dialog id="student-exam-dialog" class="student-dialog" aria-labelledby="student-exam-dialog-title">
 <header class="student-dialog-heading"><h2 id="student-exam-dialog-title">Thông tin thi tuyển</h2><button type="button" aria-label="Đóng thông tin thi tuyển" data-student-dialog-close autofocus>×</button></header>
 <?php if (!$studentDashboard['examSites']): ?>
 <p class="student-dialog-empty">Chưa có thông tin phân phòng thi cho hồ sơ của bạn.</p>
 <?php else: foreach ($studentDashboard['examSites'] as $examSite): ?>
 <section class="student-exam-ticket"><h3><?= e(catalog_display_name($examSite['tenKyThi'])) ?></h3><p class="student-exam-site"><?= e(catalog_display_name($examSite['tenDiemThi'])) ?></p>
 <?php if ($examSite['tenTruong']): ?><p><?= e(school_display_name($examSite['tenTruong'])) ?></p><?php endif; ?>
 <dl><div><dt>Số báo danh</dt><dd><?= e($profile['soBaoDanh'] ?: 'Chưa được cấp') ?></dd></div><div><dt>Phòng thi</dt><dd><?= e(catalog_display_name($examSite['tenPhong'])) ?><small><?= e($examSite['maPhong']) ?></small></dd></div></dl>
 </section>
 <?php endforeach; endif; ?>
 <div class="student-exam-schedule"><h3>Lịch thi</h3><p>Lịch thi chi tiết chưa được cập nhật trên hệ thống. Vui lòng theo dõi thông báo của kỳ thi.</p></div>
</dialog>

<?php $portal_guide_part = $portal_guide_part ?? 'all'; ?>
<?php if ($portal_guide_part !== 'support'): ?>
<section class="portal-section" id="timeline" aria-labelledby="timeline-title">
 <div class="section-heading"><div><span class="section-overline">QUY TRÌNH TUYỂN SINH</span><h2 id="timeline-title">Các giai đoạn tuyển sinh</h2></div><span class="subtle-label">Theo dõi thông báo tuyển sinh</span></div>
 <ol class="admission-timeline">
  <?php foreach ([['Tìm hiểu thông tin', 'Xem danh sách trường, chỉ tiêu và hướng dẫn.'], ['Đăng ký nguyện vọng', 'Đăng nhập, xem và sắp xếp nguyện vọng ưu tiên.'], ['Tham dự kỳ thi', 'Theo dõi thông tin kỳ thi, phòng thi và số báo danh.'], ['Tra cứu kết quả', 'Theo dõi điểm thi và kết quả xét tuyển được công bố.'], ['Xác nhận nhập học', 'Thực hiện theo hướng dẫn của trường trúng tuyển.']] as $step => [$title, $description]): ?>
   <li><span class="step-index" aria-hidden="true"><?= sprintf('%02d', $step + 1) ?></span><div><h3><?= e($title) ?></h3><p><?= e($description) ?></p></div></li>
  <?php endforeach; ?>
 </ol>
 <p class="section-note">Theo dõi thông báo của kỳ tuyển sinh để thực hiện từng bước đúng thời hạn.</p>
</section>
<?php endif; ?>
<?php if ($portal_guide_part !== 'timeline'): ?>
<section class="portal-section" id="guides" aria-labelledby="guide-title">
 <div class="section-heading"><div><span class="section-overline">THÔNG TIN & HỖ TRỢ</span><h2 id="guide-title">Hướng dẫn sử dụng</h2></div></div>
 <div class="guide-columns">
  <article><span class="guide-number">01</span><h3>Học sinh & phụ huynh</h3><p>Xem thông tin trường, đăng nhập bằng tài khoản học sinh và truy cập mục nguyện vọng. Theo dõi thông báo về thời gian công bố điểm và kết quả tuyển sinh.</p><a class="text-link" href="<?= e(url('auth/login.php?tab=student')) ?>">Đăng nhập học sinh →</a></article>
  <article><span class="guide-number">02</span><h3>Cán bộ tuyển sinh</h3><p>Đăng nhập bằng tài khoản được cấp để thực hiện nhiệm vụ. Các mục quản lý, chấm thi và công bố kết quả hiển thị theo quyền của từng tài khoản.</p><a class="text-link" href="<?= e(url('auth/login.php')) ?>">Đăng nhập cán bộ →</a></article>
 </div>
</section>
<section class="portal-section faq-section" id="faq" aria-labelledby="faq-title">
 <div class="section-heading"><div><span class="section-overline">GIẢI ĐÁP NHANH</span><h2 id="faq-title">Câu hỏi thường gặp</h2></div></div>
 <details><summary>Tôi cần chuẩn bị gì trước khi đăng ký nguyện vọng?</summary><p>Kiểm tra thông tin cá nhân, tham khảo chỉ tiêu các trường THPT và đăng nhập bằng tài khoản được cấp. Theo dõi thời gian đăng ký trong thông báo tuyển sinh.</p></details>
 <details><summary>Khi nào tôi có thể xem kết quả?</summary><p>Kết quả được cung cấp sau khi hoàn tất xử lý và công bố. Vui lòng theo dõi thông báo trên hệ thống.</p><a class="text-link" href="<?= e(url('results/')) ?>">Thông tin kết quả →</a></details>
 <details><summary>Vì sao tôi không thấy một số chức năng quản lý?</summary><p>Chức năng hiển thị theo vai trò tài khoản. Học sinh, hội đồng thi và cán bộ tuyển sinh có phạm vi truy cập khác nhau. Bạn cần đăng nhập đúng tài khoản được cấp.</p></details>
 <details><summary>Tôi cần làm gì khi quên mật khẩu?</summary><p>Liên hệ cán bộ phụ trách tài khoản tại trường hoặc quản trị hệ thống để được hỗ trợ. Không cung cấp mật khẩu cho người khác.</p></details>
</section>

<?php endif; ?>

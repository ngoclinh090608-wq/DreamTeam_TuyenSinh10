<?php $portal_guide_part = $portal_guide_part ?? 'all'; ?>
<?php if ($portal_guide_part !== 'support'): ?>
<section class="portal-section" id="timeline" aria-labelledby="timeline-title">
 <div class="section-heading"><div><span class="section-overline">LỘ TRÌNH THAM KHẢO</span><h2 id="timeline-title">Các giai đoạn tuyển sinh</h2></div><span class="subtle-label">Chưa có lịch chính thức</span></div>
 <ol class="admission-timeline">
  <?php foreach ([['Tìm hiểu thông tin', 'Xem danh sách trường, chỉ tiêu và hướng dẫn.'], ['Đăng ký nguyện vọng', 'Đăng nhập, xem và sắp xếp nguyện vọng ưu tiên.'], ['Tham dự kỳ thi', 'Theo dõi thông tin kỳ thi, phòng thi và số báo danh.'], ['Tra cứu kết quả', 'Theo dõi điểm thi và kết quả xét tuyển được công bố.'], ['Xác nhận nhập học', 'Thực hiện theo hướng dẫn của trường trúng tuyển.']] as $step => [$title, $description]): ?>
   <li><span class="step-index" aria-hidden="true"><?= sprintf('%02d', $step + 1) ?></span><div><h3><?= e($title) ?></h3><p><?= e($description) ?></p></div></li>
  <?php endforeach; ?>
 </ol>
 <p class="section-note">Quy trình minh họa cho sản phẩm học tập. Thời gian và quy định thực tế cần được đối chiếu với thông báo của cơ quan có thẩm quyền.</p>
</section>
<?php endif; ?>
<?php if ($portal_guide_part !== 'timeline'): ?>
<section class="portal-section" id="guides" aria-labelledby="guide-title">
 <div class="section-heading"><div><span class="section-overline">THÔNG TIN & HỖ TRỢ</span><h2 id="guide-title">Hướng dẫn sử dụng</h2></div></div>
 <div class="guide-columns">
  <article><span class="guide-number">01</span><h3>Học sinh & phụ huynh</h3><p>Xem thông tin trường, đăng nhập bằng tài khoản học sinh và truy cập mục nguyện vọng. Trang tra cứu công khai hiện chỉ hỗ trợ mã mẫu <code>DEMO001</code>.</p><a class="text-link" href="<?= e(url('auth/login.php?tab=student')) ?>">Đăng nhập học sinh →</a></article>
  <article><span class="guide-number">02</span><h3>Cán bộ tuyển sinh</h3><p>Đăng nhập bằng tài khoản được cấp để thực hiện nhiệm vụ. Các mục quản lý, chấm thi và công bố kết quả hiển thị theo quyền của từng tài khoản.</p><a class="text-link" href="<?= e(url('auth/login.php')) ?>">Đăng nhập cán bộ →</a></article>
 </div>
</section>
<section class="portal-section faq-section" id="faq" aria-labelledby="faq-title">
 <div class="section-heading"><div><span class="section-overline">GIẢI ĐÁP NHANH</span><h2 id="faq-title">Câu hỏi thường gặp</h2></div></div>
 <details><summary>Đây có phải cổng tuyển sinh chính thức không?</summary><p>Không. Đây là sản phẩm học tập. Dữ liệu trường, hồ sơ và kết quả trong môi trường thực hành không có giá trị tuyển sinh thực tế.</p></details>
 <details><summary>Tôi có thể tra cứu bằng thông tin nào?</summary><p>Trang tra cứu công khai đang là giao diện mẫu. Dùng mã <code>DEMO001</code> để xem kết quả minh họa; không nhập CCCD hoặc thông tin cá nhân thật.</p><a class="text-link" href="<?= e(url('results/')) ?>">Mở trang tra cứu mẫu →</a></details>
 <details><summary>Vì sao tôi không thấy một số chức năng quản lý?</summary><p>Chức năng hiển thị theo vai trò tài khoản. Học sinh, hội đồng thi và cán bộ tuyển sinh có phạm vi truy cập khác nhau. Bạn cần đăng nhập đúng tài khoản được cấp.</p></details>
 <details><summary>Tôi cần làm gì khi quên mật khẩu?</summary><p>Liên hệ người quản lý tài khoản trong nhóm thực hành để được hỗ trợ. Hệ thống hiện chưa có chức năng tự khôi phục mật khẩu công khai.</p></details>
</section>

<?php endif; ?>

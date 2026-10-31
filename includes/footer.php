<?php if (($layout ?? 'public') === 'dashboard'): ?>
    </main>
    <footer class="dash-footer">© 2026 · Cổng tuyển sinh lớp 10 <span>Thông tin tuyển sinh · Hỗ trợ học sinh</span></footer>
  </div>
</div>
<div class="dash-scrim" data-menu-close></div>
<?php else: ?>
<footer class="site-footer" id="support">
 <div class="container footer-grid">
  <div class="footer-about"><div class="footer-brand"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="46" height="46"><div><strong>Cổng thông tin tuyển sinh vào lớp 10</strong><small>Đồng hành cùng học sinh và phụ huynh</small></div></div><p>Cung cấp thông tin trường học, chỉ tiêu và hướng dẫn tuyển sinh. Hỗ trợ học sinh, phụ huynh và cán bộ theo dõi quá trình tuyển sinh.</p></div>
  <div><h2>Thông tin tuyển sinh</h2><a href="<?= e(url('admission-info/huong-dan.php')) ?>">Hướng dẫn & quy trình</a><a href="<?= e(url('schools/danh-sach.php')) ?>">Danh sách trường THPT</a><a href="<?= e(url()) ?>#timeline">Các giai đoạn tuyển sinh</a></div>
  <div><h2>Hỗ trợ sử dụng</h2><a href="<?= e(url()) ?>#faq">Câu hỏi thường gặp</a><a href="<?= e(url('results/')) ?>">Thông tin kết quả</a><a href="<?= e(url('auth/login.php')) ?>">Đăng nhập hệ thống</a></div>
 </div>
 <div class="container copyright"><span>© 2026 · Cổng tuyển sinh lớp 10</span><a href="<?= e(url()) ?>#main">Về đầu trang ↑</a></div>
</footer>
<?php endif; ?>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
</body></html>

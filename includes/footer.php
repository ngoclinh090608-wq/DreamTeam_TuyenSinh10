<?php if (($layout ?? 'public') === 'dashboard'): ?>
    </main>
    <footer class="dash-footer">© 2026 Dream Team · Đồ án môn Phát triển ứng dụng</footer>
  </div>
</div>
<div class="dash-scrim" data-menu-close></div>
<?php else: ?>
<footer class="site-footer"><div class="container footer-grid">
  <div><div class="footer-brand"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="44" height="44"><div><strong>Hệ thống tuyển sinh lớp 10</strong><small>Đồ án nhóm Dream Team</small></div></div><p>Giao diện minh họa phục vụ học tập môn Phát triển ứng dụng. Không phải cổng tuyển sinh chính thức.</p></div>
  <div><h4>Khám phá</h4><a href="<?= e(url('index.php')) ?>#timeline">Lịch tuyển sinh</a><a href="<?= e(url('index.php')) ?>#targets">Chỉ tiêu trường</a><a href="<?= e(url('index.php')) ?>#guides">Hướng dẫn</a></div>
  <div><h4>Tiện ích</h4><a href="<?= e(url('auth/login.php')) ?>">Đăng nhập</a><a href="<?= e(url('results/')) ?>">Tra cứu kết quả</a><a href="<?= e(url('admin/')) ?>">Xem giao diện quản trị</a></div>
  <div><h4>Thông tin</h4><p>Nhóm Dream Team</p><p>Trường Đại học Công nghiệp TP. Hồ Chí Minh</p></div>
</div><div class="container copyright">© 2026 DREAM TEAM <span>Bản mô phỏng · Dữ liệu trên website chỉ để minh họa</span></div></footer>
<?php endif; ?>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
</body></html>

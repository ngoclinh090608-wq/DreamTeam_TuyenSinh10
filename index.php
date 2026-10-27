<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Trang chủ'; $layout = 'public';
$sample = require __DIR__ . '/includes/sample_data.php';
require_once __DIR__ . '/services/live_data.php';
if ($liveSchools = live_home_schools()) {
    $sample['schools'] = array_map(fn($r) => ['code'=>$r[0],'name'=>$r[1],'district'=>$r[2],'quota'=>$r[3],'type'=>'THPT'], $liveSchools);
}
require __DIR__ . '/includes/header.php';
?>
<main id="main">
<section class="hero">
 <div class="hero-orb orb-one"></div><div class="hero-orb orb-two"></div>
 <div class="container hero-layout">
  <div class="hero-copy">
   <div class="hero-kicker"><span class="kicker-star">✦</span> CỔNG THÔNG TIN TUYỂN SINH LỚP 10 <span class="kicker-line"></span> 2026–2027</div>
   <h1>Mở cánh cửa<br> <span>tương lai</span> của bạn.</h1>
   <p>Giao diện tham khảo dành cho học sinh, phụ huynh và nhà trường. Theo dõi thông tin, đăng ký nguyện vọng và tra cứu kết quả tại một nơi.</p>
   <div class="hero-buttons"><a class="btn btn-primary btn-lg" href="<?= e(url('auth/login.php')) ?>">Bắt đầu đăng nhập <span>↗</span></a><a class="btn btn-white btn-lg" href="<?= e(url('results/')) ?>">Tra cứu kết quả <span>⌕</span></a></div>
   <div class="hero-caption"><span class="mini-avatars"><b>DT</b><b>10</b><b>★</b></span><span><strong>Hệ thống mô phỏng Dream Team</strong><small>Phục vụ đồ án môn Phát triển ứng dụng</small></span></div>
  </div>
  <div class="hero-visual" aria-label="Minh họa bảng thông tin tuyển sinh">
   <div class="hero-main-panel"><div class="mock-panel-top"><div class="mock-icon">✦</div><span>CỔNG TUYỂN SINH</span><div class="mock-dots">•••</div></div><div class="mock-greeting">Năm học 2026 — 2027</div><h2>Hành trình vào lớp 10</h2><p>Theo dõi các mốc tuyển sinh quan trọng.</p><div class="mock-step"><div class="mock-step-dot active">✓</div><div><strong>Tìm hiểu thông tin</strong><small>Danh sách trường & chỉ tiêu</small></div><span>Hoàn tất</span></div><div class="mock-step"><div class="mock-step-dot">02</div><div><strong>Đăng ký nguyện vọng</strong><small>Chọn tối đa 3 nguyện vọng</small></div><span>Tiếp theo</span></div><div class="mock-step"><div class="mock-step-dot">03</div><div><strong>Tra cứu kết quả</strong><small>Xem điểm thi & xét tuyển</small></div></div><a href="<?= e(url('index.php')) ?>#timeline" class="mock-panel-footer">Khám phá lộ trình <span>↗</span></a></div>
   <div class="float-card float-one"><span>✓</span><div><strong>Thông tin rõ ràng</strong><small>Tiện lợi và dễ sử dụng</small></div></div>
   <div class="float-card float-two"><span>▤</span><div><strong>03 nguyện vọng</strong><small>NV1 · NV2 · NV3</small></div></div>
  </div>
 </div>
</section>
<div class="container disclaimer-strip"><span class="disclaimer-icon">ⓘ</span><strong>Thông tin mô phỏng:</strong> Mọi ngày tháng, chỉ tiêu, tên trường và dữ liệu tra cứu trên giao diện này đều là minh họa, không phải thông báo chính thức của cơ quan giáo dục.</div>
<section class="section feature-section"><div class="container"><div class="section-intro center"><div class="eyebrow">CÔNG CỤ DÀNH CHO BẠN</div><h2>Tất cả những gì bạn cần <span>trong một nơi</span></h2><p>Truy cập nhanh các tiện ích quan trọng của kỳ tuyển sinh lớp 10.</p></div><div class="feature-grid"><a class="feature-card" href="<?= e(url('index.php')) ?>#timeline"><div class="feature-icon fi-blue">▣</div><h3>Lịch tuyển sinh</h3><p>Nắm rõ các giai đoạn của kỳ tuyển sinh.</p><span>Khám phá →</span></a><a class="feature-card" href="<?= e(url('index.php')) ?>#targets"><div class="feature-icon fi-purple">▥</div><h3>Chỉ tiêu tuyển sinh</h3><p>Tham khảo danh sách trường và chỉ tiêu.</p><span>Xem chỉ tiêu →</span></a><a class="feature-card" href="<?= e(url('results/')) ?>"><div class="feature-icon fi-mint">⌕</div><h3>Tra cứu kết quả</h3><p>Giao diện tra cứu kết quả thi và xét tuyển.</p><span>Tra cứu ngay →</span></a><a class="feature-card" href="<?= e(url('auth/login.php')) ?>"><div class="feature-icon fi-orange">↗</div><h3>Cổng học sinh</h3><p>Giao diện đăng nhập để thực hiện các thao tác tuyển sinh.</p><span>Đăng nhập →</span></a></div></div></section>
<section class="section timeline-section" id="timeline"><div class="container"><div class="section-intro center"><div class="eyebrow">LỘ TRÌNH TUYỂN SINH</div><h2>Hành trình <span>từng bước rõ ràng</span></h2><p>Các bước minh họa quy trình nghiệp vụ; ngày thực hiện sẽ được bổ sung sau.</p></div><div class="timeline-cards">
 <?php foreach([['01','Chuẩn bị thông tin','Rà soát dữ liệu học sinh và các trường.'],['02','Đăng ký nguyện vọng','Học sinh chọn NV1, NV2 và NV3.'],['03','Tổ chức kỳ thi','Cấp số báo danh, phân phòng và dự thi.'],['04','Công bố điểm thi','Thực hiện chấm thi và xử lý phúc khảo.'],['05','Xét tuyển & nhập học','Công bố kết quả, xác nhận nhập học.']] as [$number,$label,$desc]): ?>
 <div class="timeline-card"><div class="timeline-number"><?= e($number) ?></div><div class="timeline-body"><h3><?= e($label) ?></h3><p><?= e($desc) ?></p></div></div>
 <?php endforeach; ?>
 </div></div></section>
<section class="section schools-section" id="targets"><div class="container"><div class="section-line"><div class="section-intro"><div class="eyebrow">CHỈ TIÊU & TRƯỜNG HỌC</div><h2>Khám phá <span>ngôi trường phù hợp</span></h2><p>Danh sách trường THPT và chỉ tiêu tuyển sinh.</p></div><a class="btn btn-outline" href="<?= e(url('schools/')) ?>">Xem giao diện quản lý →</a></div>
 <div class="school-filter"><div class="filter-tabs"><button class="chip active" data-school-filter="all" aria-pressed="true">Tất cả trường</button><button class="chip" data-school-filter="1" aria-pressed="false">Khu vực 1</button><button class="chip" data-school-filter="2" aria-pressed="false">Khu vực 2</button><button class="chip" data-school-filter="3" aria-pressed="false">Khu vực 3</button></div><label class="search-input"><span>⌕</span><input type="search" placeholder="Tìm tên trường..." aria-label="Tìm trường" data-school-search></label></div>
 <div class="school-grid" id="schools"><?php foreach($sample['schools'] as $i=>$school): ?><div class="school-card" data-school-item data-region="<?= e(substr($school['district'],-1)) ?>"><div class="school-card-top"><div class="school-symbol">▤</div><span class="school-type">TRƯỜNG THPT</span></div><h3><?= e($school['name']) ?></h3><p>⌖ <?= e($school['district']) ?></p><div class="school-bottom"><div><small>Chỉ tiêu</small><strong><?= e($school['quota']) ?> <em>học sinh</em></strong></div><span class="school-arrow">↗</span></div></div><?php endforeach; ?></div><p class="empty-state" id="school-empty" hidden>Không có trường nào khớp bộ lọc hiện tại.</p>
</div></section>
<section class="section guide-section" id="guides"><div class="container guide-layout"><div><div class="eyebrow">HƯỚNG DẪN NHANH</div><h2>Sẵn sàng cho<br><span>kỳ tuyển sinh?</span></h2><p>Bốn bước cơ bản để sử dụng giao diện hệ thống.</p><a class="btn btn-primary" href="<?= e(url('auth/login.php')) ?>">Xem giao diện đăng nhập ↗</a></div><div class="guide-list"><div><b>01</b><span><strong>Xem thông tin</strong><small>Tìm hiểu các trường THPT và các mốc thời gian.</small></span></div><div><b>02</b><span><strong>Đăng nhập học sinh</strong><small>Đi đến khu vực dành cho thí sinh.</small></span></div><div><b>03</b><span><strong>Đăng ký nguyện vọng</strong><small>Chọn tối đa ba nguyện vọng theo thứ tự ưu tiên.</small></span></div><div><b>04</b><span><strong>Tra cứu kết quả</strong><small>Nhập số báo danh hoặc mã định danh để xem kết quả.</small></span></div></div></div></section>
<section class="container closing-cta"><div><div class="eyebrow light">DREAM TEAM · GIAO DIỆN THAM KHẢO</div><h2>Sẵn sàng trải nghiệm<br>hệ thống mới?</h2><p>Khám phá bộ giao diện chung của trang công khai và trang quản trị.</p></div><div><a class="btn btn-white btn-lg" href="<?= e(url('admin/')) ?>">Xem giao diện quản trị ↗</a></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

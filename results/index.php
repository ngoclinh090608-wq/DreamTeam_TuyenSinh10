<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
$lookup_view = ($_GET['view'] ?? '') === 'diem-thi' ? 'diem-thi' : 'tuyen-sinh';
$page_title = $lookup_view === 'diem-thi' ? 'Tra cứu điểm thi' : 'Kết quả tuyển sinh';
$public_active = $lookup_view === 'diem-thi' ? 'scores' : 'results';
$layout = 'public';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main" class="result-main">
 <div class="result-orb result-orb-one"></div><div class="result-orb result-orb-two"></div>
 <div class="container result-wrap"><div class="result-icon">⌕</div><div class="eyebrow center"><span></span> CỔNG TRA CỨU TUYỂN SINH</div><h1><?= e($page_title) ?> <span>vào lớp 10</span></h1><p class="result-subtitle">Nhập mã định danh hoặc số báo danh để xem bố cục kết quả mô phỏng.</p>
 <section class="result-card"><div class="result-card-title"><div><h2>Thông tin tra cứu</h2><p>Hãy nhập thông tin vào biểu mẫu dưới đây.</p></div><span class="panel-pill">2026–2027</span></div><form id="lookup-demo-form"><div class="field"><label for="lookup-id">Số báo danh hoặc CCCD / Định danh cá nhân <span>*</span></label><div class="input-with-icon"><span>▤</span><input id="lookup-id" name="lookup_id" value="<?= e(is_string($_GET['lookup_id'] ?? null) ? mb_substr($_GET['lookup_id'], 0, 50) : '') ?>" maxlength="50" placeholder="Ví dụ: DEMO001" required autocomplete="off"></div><small class="field-note">Thử <code>DEMO001</code> để xem thẻ kết quả mẫu.</small></div><button class="btn btn-primary btn-lg w-full" type="submit">Tra cứu kết quả <span>⌕</span></button></form><div class="result-note"><span>ⓘ</span> Điểm và kết quả bên dưới chỉ là dữ liệu mẫu, không phải kết quả thực tế.</div></section>
 <section class="lookup-result" id="lookup-result" aria-live="polite" hidden><div class="lookup-result-header"><div><span class="lookup-badge">KẾT QUẢ MẪU</span><h2>Thông tin thí sinh</h2></div><span class="status status-success">Dữ liệu minh họa</span></div><div class="lookup-identity"><div><span>Số báo danh</span><strong>DEMO001</strong></div><div><span>Họ và tên</span><strong>Thí sinh mẫu A</strong></div><div><span>Năm tuyển sinh</span><strong>2026–2027</strong></div></div><h3>Điểm các môn (mẫu)</h3><div class="score-grid"><div>Toán <strong>8.00</strong></div><div>Ngữ văn <strong>7.50</strong></div><div>Ngoại ngữ <strong>8.25</strong></div></div><p class="result-final">Trạng thái xét tuyển: <strong>Chưa công bố trong hệ thống demo</strong></p></section>
 <div class="result-tips"><div><span>♙</span><div><strong>Thông tin bảo mật</strong><p>Không chia sẻ CCCD hoặc mật khẩu thật trong bản giao diện mẫu.</p></div></div><div><span>▤</span><div><strong>Hệ thống chính thức</strong><p>Kết quả thực tế chỉ có giá trị khi được cơ quan có thẩm quyền công bố.</p></div></div></div>
 </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/services/csrf.php';
require_once dirname(__DIR__) . '/services/public_score_service.php';
app_session_start();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$number = ''; $birth = ''; $scores = []; $error = ''; $searched = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_verify()) throw new BusinessException('Phiên yêu cầu không hợp lệ. Vui lòng tải lại trang.');
        $number = is_string($_POST['sbd'] ?? null) ? trim($_POST['sbd']) : '';
        $birth = is_string($_POST['ngaySinh'] ?? null) ? $_POST['ngaySinh'] : '';
        $scores = public_published_scores($number, $birth);
        $searched = true;
    } catch (BusinessException $e) { $error = $e->getMessage(); }
    catch (Throwable $e) { error_log('public score lookup: '.$e->getMessage()); $error = 'Không thể tra cứu lúc này. Vui lòng thử lại sau.'; }
}
$page_title = 'Tra cứu điểm thi'; $layout = 'public'; $public_active = 'scores';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main" class="portal-main"><div class="container">
<section class="panel" style="max-width:760px;margin:24px auto;padding:24px" aria-labelledby="lookup-title">
<h1 id="lookup-title">Tra cứu điểm thi</h1>
<p>Học sinh và phụ huynh có thể tra cứu không cần đăng nhập. Nhập số báo danh và ngày sinh của thí sinh; hệ thống chỉ hiển thị điểm đã công bố.</p>
<?php if ($error !== ''): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="<?= e(url('tra-cuu-diem/')) ?>">
<?= csrf_field() ?>
<div class="field"><label for="lookup-sbd">Số báo danh</label><input id="lookup-sbd" name="sbd" maxlength="20" value="<?= e($number) ?>" autocomplete="off" required></div>
<div class="field"><label for="lookup-birth">Ngày sinh</label><input id="lookup-birth" name="ngaySinh" type="date" value="<?= e($birth) ?>" required></div>
<button class="btn btn-primary" type="submit">Tra cứu điểm thi</button>
</form>
<?php if ($searched && !$scores): ?><p role="status">Không tìm thấy điểm đã công bố phù hợp với thông tin bạn nhập. Vui lòng kiểm tra số báo danh, ngày sinh hoặc thời gian công bố điểm.</p><?php endif; ?>
<?php if ($scores): ?><section class="ui-mt" aria-labelledby="scores-title"><h2 id="scores-title">Điểm thi đã công bố</h2>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Kỳ thi</th><th>Môn thi</th><th>Điểm</th></tr></thead><tbody>
<?php foreach ($scores as $row): ?><tr><td><?= e(catalog_display_name($row['tenKyThi'])) ?></td><td><?= e($row['tenMonThi']) ?></td><td><?= e(number_format((float)$row['diemThi'],2)) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section><?php endif; ?>
<p class="ui-mt"><a href="<?= e(url('auth/login.php')) ?>">Đăng nhập để đăng ký nguyện vọng, xem kết quả tuyển sinh và gửi yêu cầu phúc khảo</a></p>
</section></div></main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

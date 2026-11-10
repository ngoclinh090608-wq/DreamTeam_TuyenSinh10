<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/candidate_number_service.php';
$user = require_role(['BAN_TUYEN_SINH']);
$error = null; $details = []; $rows = []; $councils = []; $totalEligible = 0; $totalPending = 0;
$council = ui_input('hoiDong'); $state = ui_input('trangThai') ?: 'pending';
try {
    $councils = db()->query('SELECT h.maHoiDongThi,h.tenHoiDongThi,k.tenKyThi FROM HoiDongThi h JOIN KyThi k ON k.maKyThi=h.maKyThi ORDER BY h.maKyThi,h.maHoiDongThi')->fetchAll();
    $exam = candidate_number_exam_scope($council);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($csrf = ui_csrf_error()) throw new BusinessException($csrf);
        if (ui_input('confirm') !== '1') throw new BusinessException('Vui lòng xác nhận thao tác cấp/thu hồi SBD.');
        $action = ui_input('action') ?: 'issue';
        if (!in_array($action, ['issue','issue_scope','revoke','revoke_scope'], true)) throw new BusinessException('Thao tác không hợp lệ.');
        $ids = $_POST['candidates'] ?? [];
        if (!is_array($ids)) throw new BusinessException('Danh sách thí sinh không hợp lệ.');
        if (in_array($action, ['issue_scope','revoke_scope'], true)) {
            $pending = candidate_number_directory($council, $action === 'revoke_scope' ? 'issued' : 'pending');
            if (ui_input('expectedCount') !== (string)count($pending) || !hash_equals(hash('sha256', json_encode(array_column($pending, 'maThiSinh'))), ui_input('scopeFingerprint'))) throw new BusinessException('Số hồ sơ đã thay đổi. Vui lòng kiểm tra và xác nhận lại.');
            $ids = array_column($pending, 'maThiSinh');
        }
        if ($council !== '') {
            $allowed = array_fill_keys(array_column(candidate_number_directory($council, 'all'), 'maThiSinh'), true);
            foreach ($ids as $id) if (!is_string($id) || !isset($allowed[$id])) throw new BusinessException('Thí sinh không thuộc phạm vi hội đồng đã chọn.');
        }
        if (in_array($action, ['revoke','revoke_scope'], true)) {
            $count = candidate_revoke_numbers($user, $ids, true);
            flash_set('success', 'Đã thu hồi số báo danh của ' . $count . ' thí sinh.');
        } else {
            $issued = candidate_issue_numbers($user, $ids, $exam);
            flash_set('success', 'Đã cấp số báo danh cho ' . count($issued) . ' thí sinh.');
        }
        ui_redirect('candidates/cap-so-bao-danh.php', ['hoiDong'=>$council,'trangThai'=>in_array($action, ['revoke','revoke_scope'], true) ? 'pending' : 'issued']);
    }
    $rows = candidate_number_directory($council, $state);
    if (ui_input('export') === '1') {
        header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="danh-sach-sbd.csv"');
        $out = fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['Mã thí sinh','Mã hồ sơ','Họ tên','Số báo danh','Phòng thi']);
        foreach ($rows as $row) {
            $cells = [$row['maThiSinh'],$row['maHoSo'],$row['hoTen'],$row['soBaoDanh'] ?? '',$row['phongThi'] ?? ''];
            fputcsv($out,array_map(static fn($cell) => preg_match('/\A[=+@\-\t\r\n]/u',(string)$cell) ? "'".$cell : $cell,$cells));
        }
        fclose($out); exit;
    }
} catch (BusinessException $e) { $error=$e->getMessage(); $details=$e->details(); }
catch (Throwable $e) { error_log('SBD: '.$e->getMessage());$error='Không thể xử lý số báo danh. Giao dịch lỗi không lưu thay đổi.'; }
if ($error) { try { $rows=candidate_number_directory($council,$state); } catch (Throwable $e) { error_log('SBD reload: '.$e->getMessage()); } }
try {
    $scopeRows = candidate_number_directory($council, 'all');
    foreach ($scopeRows as $row) if ((int)$row['dieuKienDuThi'] === 1 && $row['trangThaiDangKyDuThi'] === 'Đã duyệt') {
        $totalEligible++; if ($row['soBaoDanh'] === null || $row['soBaoDanh'] === '') $totalPending++;
    }
} catch (Throwable $e) { error_log('SBD totals: '.$e->getMessage()); }
ui_page_start('candidates','Cấp số báo danh','Hồ sơ dự thi','Cấp theo tên A–B–C sau khi chốt đăng ký và hoàn tất phân phòng. Cấp bổ sung giữ nguyên số đã có.','UC03');
ui_flashes(); if ($error) ui_notice('error',$error,$details);
?>
<section class="panel"><form method="get" class="ui-search">
<div class="field"><label for="hoiDong">Phạm vi</label><select id="hoiDong" name="hoiDong"><option value="">Toàn thành phố</option><?php foreach($councils as $h): ?><option value="<?= e($h['maHoiDongThi']) ?>"<?= $council===$h['maHoiDongThi']?' selected':'' ?>><?= e($h['tenHoiDongThi'].' · '.$h['tenKyThi']) ?></option><?php endforeach; ?></select><small>Phạm vi hội đồng theo kỳ thi được gắn trong dữ liệu.</small></div>
<div class="field"><label for="trangThai">Trạng thái SBD</label><select id="trangThai" name="trangThai"><?php foreach(['pending'=>'Chỉ cấp cho thí sinh chưa có SBD','issued'=>'Đã cấp SBD','all'=>'Tất cả'] as $v=>$label): ?><option value="<?= e($v) ?>"<?= $state===$v?' selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div><button class="btn btn-outline" type="submit">Xem danh sách</button>
</form></section>
<section class="panel ui-mt"><p><?= $totalEligible ?> hồ sơ đủ điều kiện, đã duyệt · <?= $totalPending ?> chưa có SBD · <?= count($rows) ?> hồ sơ trong bộ lọc · Thứ tự theo tên, họ và tên đệm A–B–C.</p>
<a class="btn btn-outline" href="<?= e(url('candidates/cap-so-bao-danh.php',['hoiDong'=>$council,'trangThai'=>$state,'export'=>'1'])) ?>">Xuất danh sách SBD (CSV)</a>
<form method="post" onsubmit="return window.confirm('Xác nhận cấp hoặc thu hồi SBD theo danh sách đã chọn?');">
<?= csrf_field() ?><input type="hidden" name="confirm" value="1"><input type="hidden" name="hoiDong" value="<?= e($council) ?>"><input type="hidden" name="trangThai" value="<?= e($state) ?>"><input type="hidden" name="scopeFingerprint" value="<?= e(hash('sha256',json_encode(array_column($rows,'maThiSinh')))) ?>"><input type="hidden" name="expectedCount" value="<?= count($rows) ?>">
<div class="table-scroll"><table class="data-table"><thead><tr><th>Chọn</th><th>Mã thí sinh</th><th>Mã hồ sơ</th><th>Họ tên</th><th>SBD</th><th>Phòng thi</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><input type="checkbox" name="candidates[]" value="<?= e($row['maThiSinh']) ?>" aria-label="Chọn <?= e($row['maThiSinh']) ?>"></td><td><?= e($row['maThiSinh']) ?></td><td><?= e($row['maHoSo']) ?></td><td><?= e($row['hoTen']) ?></td><td><?= e($row['soBaoDanh'] ?? 'Chưa có SBD') ?></td><td><?= e($row['phongThi'] ?? 'Chưa phân phòng') ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6"><?= $error?'Chưa có danh sách để thực hiện thao tác.':($state==='pending'?'Hoàn tất: không còn hồ sơ đã duyệt đang chờ cấp SBD.':'Không có hồ sơ phù hợp.') ?></td></tr><?php endif; ?>
</tbody></table></div><div class="ui-actions ui-mt">
<?php if($state!=='issued'): ?><button class="btn btn-primary" name="action" value="issue" type="submit"<?= !$rows?' disabled':'' ?>>Xác nhận cấp số báo danh</button><?php endif; ?>
<?php if($state==='pending'): ?><button class="btn btn-outline" name="action" value="issue_scope" type="submit"<?= !$rows?' disabled':'' ?>>Cấp cho toàn bộ <?= count($rows) ?> hồ sơ trong phạm vi</button><?php else: ?><button class="btn btn-outline" name="action" value="revoke" type="submit"<?= !$rows?' disabled':'' ?>>Thu hồi SBD đã chọn</button><?php if($state==='issued'): ?><button class="btn btn-outline" name="action" value="revoke_scope" type="submit"<?= !$rows?' disabled':'' ?>>Thu hồi toàn bộ <?= count($rows) ?> SBD trong phạm vi</button><?php endif; ?><?php endif; ?>
<a class="btn btn-outline" href="<?= e(url('candidates/')) ?>">Danh sách thí sinh</a></div></form></section>
<?php ui_page_end(); ?>

<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/admission_service.php';
$actor = require_module('admission');
$examId = ui_input('exam'); $error = ''; $plan = null; $schools = []; $exams = []; $history = []; $registrations = [];
$options = ['weights'=>['TOAN'=>1,'VAN'=>1,'NN'=>1], 'allocation'=>[], 'ties'=>[], 'bonuses'=>[]];
try {
    $exams = db()->query('SELECT k.*,d.namTuyenSinh,d.tenDot FROM KyThi k JOIN DotTuyenSinh d ON d.maDot=k.maDot ORDER BY d.namTuyenSinh DESC,k.maKyThi')->fetchAll();
    if ($examId === '' && $exams) $examId = $exams[0]['maKyThi'];
    $stmt = db()->prepare("SELECT i.*,p.tenTruongTHPT FROM ThongTinTuyenSinh i JOIN TruongTHPT p ON p.maTruongTHPT=i.maTruongTHPT JOIN KyThi k ON k.maDot=i.maDot WHERE k.maKyThi=? ORDER BY i.maTruongTHPT,i.maThongTin");
    $stmt->execute([$examId]); $schools = $stmt->fetchAll();
    foreach ($schools as $school) $options['allocation'][$school['maTruongTHPT']] = [1=>(int)$school['chiTieuTuyenSinh'],2=>0,3=>0];
    $counts = db()->prepare('SELECT n.maTruongTHPT,n.thuTuNguyenVong,COUNT(*) AS total FROM NguyenVong n JOIN QuyDinhNguyenVong q ON q.maQuyDinh=n.maQuyDinh JOIN DotTuyenSinh d ON d.namTuyenSinh=q.namTuyenSinh JOIN KyThi k ON k.maDot=d.maDot WHERE k.maKyThi=? GROUP BY n.maTruongTHPT,n.thuTuNguyenVong');
    $counts->execute([$examId]);
    foreach ($counts->fetchAll() as $count) $registrations[$count['maTruongTHPT']][$count['thuTuNguyenVong']] = (int)$count['total'];
    $bonusText = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($csrfError = ui_csrf_error()) throw new BusinessException($csrfError);
        if (ui_input('action') === 'save') {
            $preview = $_SESSION['uc17_preview'] ?? null;
            if (!$preview || $preview['exam'] !== $examId || !hash_equals($preview['nonce'], ui_input('preview')) || time()-$preview['time'] > 1800) throw new BusinessException('Bản xem trước đã hết hạn; vui lòng tính lại.');
            if (ui_input('confirm') !== '1') throw new BusinessException('Vui lòng xác nhận đã kiểm tra danh sách dự kiến.');
            $id = uc17_save($actor, $examId, $preview['fingerprint'], $preview['options']);
            unset($_SESSION['uc17_preview']);
            flash_set('success', 'Xét tuyển thành công. Đã lưu danh sách ' . $id . '; chưa phê duyệt hoặc công bố.');
            ui_redirect('admission/', ['exam'=>$examId]);
        }
        if (ui_input('action') !== 'preview') throw new BusinessException('Thao tác không hợp lệ.');
        // Hủy bản xem trước cũ ngay cả khi lần tính mới bị lỗi.
        unset($_SESSION['uc17_preview']);
        $options = ['weights'=>is_array($_POST['weights'] ?? null) ? $_POST['weights'] : [],
            'allocation'=>is_array($_POST['allocation'] ?? null) ? $_POST['allocation'] : [],
            'ties'=>is_array($_POST['ties'] ?? null) ? $_POST['ties'] : [], 'bonuses'=>[],
            'closed'=>ui_input('closed') === '1','appealsEnded'=>ui_input('appealsEnded') === '1'];
        $bonusText = ui_input('bonuses');
        foreach (preg_split('/\R/u', $bonusText) as $line) {
            if (trim($line) === '') continue;
            $cells = array_map('trim', explode(',', $line));
            if (count($cells) !== 3 || $cells[0] === '' || isset($options['bonuses'][$cells[0]])) throw new BusinessException('Mỗi dòng điểm cộng phải là: mã thí sinh, điểm ưu tiên, điểm khuyến khích; không lặp mã.');
            $options['bonuses'][$cells[0]] = ['priority'=>$cells[1], 'encouragement'=>$cells[2]];
        }
        $plan = uc17_preview($examId, $options);
        if (!$plan['unresolved']) $_SESSION['uc17_preview'] = ['exam'=>$examId,'options'=>$options,'fingerprint'=>$plan['fingerprint'],'nonce'=>bin2hex(random_bytes(16)),'time'=>time()];
    }
    $stmt = db()->prepare('SELECT * FROM XetTuyenLanChay WHERE maKyThi=? ORDER BY thoiGian DESC,maLan DESC LIMIT 10');
    $stmt->execute([$examId]); $history = $stmt->fetchAll();
} catch (BusinessException $e) { $error = $e->getMessage(); }
catch (Throwable $e) { error_log('UC17: ' . $e->getMessage()); $error = 'Không thể tải/lưu xét tuyển. Kiểm tra kết nối và chạy migration UC17 nếu chưa cài. Dữ liệu chính thức được giữ nguyên khi giao dịch lỗi.'; }
ui_page_start('admission','Xét tuyển NV1 – NV2 – NV3','HỘI ĐỒNG TUYỂN SINH','Phân bổ chỉ tiêu, xem trước và xác nhận lưu kết quả theo đặc tả UC17.','UC17');
ui_flashes();
if ($error !== '') ui_notice('error',$error);
?>
<section class="panel" style="padding:20px;margin-bottom:20px">
<form method="get" action="<?= e(url('admission/')) ?>">
<label for="exam-select">Kỳ thi</label><select id="exam-select" name="exam" required><?php foreach ($exams as $exam): ?><option value="<?= e($exam['maKyThi']) ?>"<?= $examId === $exam['maKyThi'] ? ' selected' : '' ?>><?= e($exam['tenKyThi']) ?></option><?php endforeach; ?></select>
<button class="btn btn-outline" type="submit">Tải chỉ tiêu</button></form>
</section>
<section class="panel" style="padding:20px;margin-bottom:20px">
<h2>Cấu hình xét tuyển</h2>
<p>Điểm xét tuyển = Toán × hệ số + Ngữ văn × hệ số + Ngoại ngữ × hệ số + điểm ưu tiên + điểm khuyến khích. Điểm sau phúc khảo được dùng nếu đã có kết quả. Nhập hệ số theo quy chế nhóm áp dụng.</p>
<p>Xét lần lượt NV1 → NV2 → NV3. Mỗi học sinh chỉ trúng tuyển một nguyện vọng. Chỉ tiêu từng NV độc lập, không tự chuyển chỉ tiêu thừa sang NV sau. Hiện hỗ trợ thi tuyển hệ thường; nguyện vọng chuyên cần quy định và chỉ tiêu riêng.</p>
<form method="post" action="<?= e(url('admission/')) ?>">
<?= csrf_field() ?><input type="hidden" name="exam" value="<?= e($examId) ?>"><input type="hidden" name="action" value="preview">
<div class="field-row"><?php foreach (['TOAN'=>'Toán','VAN'=>'Ngữ văn','NN'=>'Ngoại ngữ'] as $id=>$label): ?><div class="field"><label for="weight-<?= e($id) ?>">Hệ số <?= e($label) ?></label><input id="weight-<?= e($id) ?>" type="number" min="0.01" max="10" step="0.01" name="weights[<?= e($id) ?>]" value="<?= e((string)($options['weights'][$id] ?? 1)) ?>" required></div><?php endforeach; ?></div>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Trường</th><th>Tổng chỉ tiêu</th><?php foreach ([1,2,3] as $nv): ?><th>Chỉ tiêu NV<?= $nv ?></th><th>Bằng điểm NV<?= $nv ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($schools as $school): $id=$school['maTruongTHPT']; ?><tr><td><?= e($school['tenTruongTHPT']) ?><br><small>Đăng ký NV1/NV2/NV3: <?= (int)($registrations[$id][1] ?? 0) ?>/<?= (int)($registrations[$id][2] ?? 0) ?>/<?= (int)($registrations[$id][3] ?? 0) ?></small></td><td><?= (int)$school['chiTieuTuyenSinh'] ?></td><?php foreach ([1,2,3] as $nv): ?><td><input aria-label="<?= e($id . ' chỉ tiêu NV' . $nv) ?>" type="number" min="0" step="1" style="width:85px" name="allocation[<?= e($id) ?>][<?= $nv ?>]" value="<?= e((string)($options['allocation'][$id][$nv] ?? 0)) ?>" required></td><td><select aria-label="<?= e($id . ' bằng điểm NV' . $nv) ?>" name="ties[<?= e($id) ?>][<?= $nv ?>]"><?php foreach ([''=>'Chưa chọn','all'=>'A: Nhận tất cả bằng điểm','higher'=>'B: Nâng điểm chuẩn'] as $value=>$label): ?><option value="<?= e($value) ?>"<?= ($options['ties'][$id][$nv] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></div>
<p>Phương án A có thể vượt chỉ tiêu; phương án B chỉ nhận nhóm điểm cao hơn, chấp nhận tuyển thiếu. Khi chưa chọn, danh sách chỉ là tạm tính và không thể lưu.</p>
<div class="field"><label for="bonuses">Điểm ưu tiên/khuyến khích đã được xác minh (tùy chọn)</label><textarea id="bonuses" name="bonuses" rows="4" placeholder="TS001, 1.5, 0.5"><?= e($bonusText ?? '') ?></textarea><p>Định dạng mỗi dòng: mã thí sinh, điểm ưu tiên, điểm khuyến khích. Không khai báo nghĩa là 0; số thập phân dùng dấu chấm. Các giá trị và người xác nhận được lưu vết khi xét tuyển.</p></div>
<p><label><input type="checkbox" name="closed" value="1"<?= !empty($options['closed']) ? ' checked' : '' ?> required> Tôi xác nhận dữ liệu điểm chính thức đã chốt.</label></p>
<p><label><input type="checkbox" name="appealsEnded" value="1"<?= !empty($options['appealsEnded']) ? ' checked' : '' ?> required> Tôi xác nhận giai đoạn phúc khảo đã kết thúc.</label></p>
<p>Hệ thống kiểm tra thêm điểm đã chấm/công bố và các yêu cầu phúc khảo đã có kết quả. Hai xác nhận trên do Hội đồng chịu trách nhiệm vì dữ liệu hiện tại chưa có lịch đóng phúc khảo.</p>
<button class="btn btn-primary" type="submit"<?= !$schools ? ' disabled' : '' ?>>Chạy xét tuyển và xem trước</button>
</form></section>
<?php if ($plan): ?>
<section class="panel" style="padding:20px;margin-bottom:20px"><h2>Danh sách dự kiến</h2>
<p><?= (int)$plan['accepted'] ?> thí sinh dự kiến trúng tuyển. Chưa ghi dữ liệu chính thức.</p>
<?php foreach ($plan['warnings'] as $warning) ui_notice('warning',$warning); ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Trường</th><th>NV</th><th>Số trúng tuyển</th><th>Điểm chuẩn dự kiến</th></tr></thead><tbody><?php foreach ($plan['cutoffs'] as $school=>$priorities): foreach ($priorities as $nv=>$cutoff): ?><tr><td><?= e($school) ?></td><td>NV<?= (int)$nv ?></td><td><?= (int)$cutoff['count'] ?></td><td><?= $cutoff['score'] === null ? '—' : e(number_format($cutoff['score'],2)) ?></td></tr><?php endforeach; endforeach; ?></tbody></table></div>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Thí sinh</th><th>Họ tên</th><th>NV</th><th>Trường</th><th>Điểm xét tuyển</th><th>Kết quả</th><th>Lý do</th></tr></thead><tbody><?php foreach ($plan['rows'] as $row): ?><tr><td><?= e($row['maThiSinh']) ?></td><td><?= e($row['hoTen']) ?></td><td>NV<?= (int)$row['thuTuNguyenVong'] ?></td><td><?= e($row['maTruongTHPT']) ?></td><td><?= $row['diem'] === null ? '—' : e(number_format($row['diem'],2)) ?></td><td><?= e($row['trangThai']) ?></td><td><?= e($row['lyDo']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if (!$plan['unresolved']): ?><form method="post" action="<?= e(url('admission/')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="exam" value="<?= e($examId) ?>"><input type="hidden" name="preview" value="<?= e($_SESSION['uc17_preview']['nonce']) ?>"><p><label><input type="checkbox" name="confirm" value="1" required> Tôi đã kiểm tra danh sách dự kiến và xác nhận lưu kết quả.</label></p><button class="btn btn-primary" type="submit">Lưu kết quả xét tuyển chính thức</button></form><?php endif; ?>
</section><?php endif; ?>
<section class="panel" style="padding:20px"><h2>Lịch sử xét tuyển</h2><?php if (!$history): ?><p>Chưa có lần xét tuyển UC17 cho kỳ thi này.</p><?php else: ?><div class="table-scroll"><table class="data-table"><thead><tr><th>Thời gian</th><th>Người xác nhận</th><th>Danh sách</th><th>Trúng tuyển</th><th>Cấu hình đã lưu</th></tr></thead><tbody><?php foreach ($history as $run): ?><tr><td><?= e($run['thoiGian']) ?></td><td><?= e($run['nguoiThucHien']) ?></td><td><?= e($run['maDanhSach']) ?></td><td><?= (int)$run['tongTrungTuyen'] ?></td><td><details><summary>Xem cấu hình và điểm chuẩn</summary><pre style="white-space:pre-wrap"><?= e(json_encode(json_decode($run['thongSo'],true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre></details></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?><p>Kết quả được chuyển sang UC22 để phê duyệt/công bố theo quy trình; trang này không tự công bố kết quả.</p></section>
<?php ui_page_end(); ?>

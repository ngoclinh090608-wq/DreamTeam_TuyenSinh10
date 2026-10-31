<?php
$publishedByWish = [];
foreach ($studentDashboard['results'] as $publishedResult) $publishedByWish[$publishedResult['maNguyenVong']] = $publishedResult;
$scoresComplete = $studentDashboard['paperCount'] > 0 && count($studentDashboard['scores']) >= $studentDashboard['paperCount'];
?>
<fieldset class="student-preference-group"><legend>Nguyện vọng | Thi tuyển</legend>
<?php foreach ($studentDashboard['wishes'] as $wish):
    $officialResult = $publishedByWish[$wish['maNguyenVong']] ?? null;
    $hasFinalResult = $officialResult && in_array($officialResult['trangThaiXetTuyen'], ['Trúng tuyển','Không trúng tuyển'], true);
    $isRejected = $hasFinalResult && $officialResult['trangThaiXetTuyen'] === 'Không trúng tuyển';
    $milestones = [
        ['Đã đăng ký', true, 'Nguyện vọng đã ghi nhận'],
        ['Duyệt hồ sơ', $profile['trangThaiDangKyDuThi'] === 'Đã duyệt', $profile['trangThaiDangKyDuThi']],
        ['Cấp số báo danh', (bool)$profile['soBaoDanh'], $profile['soBaoDanh'] ?: 'Chưa được cấp'],
        ['Công bố điểm', $scoresComplete, $scoresComplete ? 'Đã công bố' : 'Chờ công bố đầy đủ'],
        ['Công bố xét tuyển', (bool)$hasFinalResult, $hasFinalResult ? 'Đã công bố' : 'Chưa công bố'],
        ['Kết quả', (bool)$hasFinalResult, $hasFinalResult ? $officialResult['trangThaiXetTuyen'] : 'Chưa công bố'],
    ];
?>
<details class="student-preference"<?= (int)$wish['thuTuNguyenVong'] === 1 ? ' open' : '' ?>>
 <summary><strong>Nguyện vọng <?= (int)$wish['thuTuNguyenVong'] ?></strong><span><?= e(school_display_name($wish['tenTruongTHPT'])) ?></span></summary>
 <ol class="student-progress" aria-label="Trạng thái nguyện vọng <?= (int)$wish['thuTuNguyenVong'] ?>">
 <?php foreach ($milestones as $stepIndex => [$stepLabel, $complete, $stepStatus]): $stepClass = $stepIndex === 5 && $isRejected ? 'is-rejected' : ($complete ? 'is-complete' : 'is-pending'); ?>
  <li class="<?= $stepClass ?>"><span class="student-progress-marker" aria-hidden="true"><?= $stepIndex === 5 && $isRejected ? '×' : ($complete ? '✓' : $stepIndex + 1) ?></span><strong><?= e($stepLabel) ?></strong><small><?= e($stepStatus) ?></small></li>
 <?php endforeach; ?>
 </ol>
</details>
<?php endforeach; ?>
<?php if (!$studentDashboard['wishes']): ?><p>Bạn chưa có nguyện vọng đăng ký.</p><?php endif; ?>
</fieldset>

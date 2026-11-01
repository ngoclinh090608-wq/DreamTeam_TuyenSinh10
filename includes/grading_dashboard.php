<?php $g = $gradingDashboard; ?>
<div class="page-head"><div><div class="eyebrow"><span></span><?= e($module['eyebrow']) ?></div><h1><?= e($module['title']) ?></h1><p><?= e($module['desc']) ?></p></div><div class="page-head-actions"><span class="uc-tag">UC05 · UC12–UC14</span></div></div>
<?php if (!$live): ?><div class="notice notice-blue" role="alert"><div><strong>Không tải được dữ liệu chấm thi.</strong> Hãy kiểm tra kết nối cơ sở dữ liệu và thử lại.</div></div><?php return; endif; ?>

<div class="metric-grid">
 <?php foreach ([
   [$g['tongBai'],'Tổng số bài thi','Tất cả bài trong kỳ','sky'],
   [$g['daCham'],'Bài đã chấm','Đã có kết quả hợp lệ','mint'],
   [$g['chuaCham'],'Bài chưa chấm','Cần tiếp tục xử lý','orange'],
   [$g['daCongBo'],'Điểm đã công bố',$g['lichSu'].' lần chỉnh sửa','purple'],
 ] as [$value,$label,$hint,$color]): ?>
 <div class="metric-card <?= e($color) ?>"><div class="metric-icon">✳</div><div class="metric-value"><?= (int)$value ?></div><div class="metric-label"><?= e($label) ?></div><div class="metric-hint"><?= e($hint) ?></div></div>
 <?php endforeach; ?>
</div>

<div class="dash-panels grading-dashboard-panels">
 <section class="panel"><div class="panel-heading"><div><h2>Tiến độ chấm theo môn</h2><p>Số bài đã chấm trên tổng số bài của từng kỳ thi và môn thi</p></div></div>
  <div class="bars"><?php foreach ($g['theoMon'] as $row): $total=(int)$row['tongBai']; $done=(int)$row['daCham']; $pc=$total?(int)round($done/$total*100):0; ?>
   <div><span><strong><?= e($row['tenMonThi']) ?></strong><small><?= e(catalog_display_name($row['tenKyThi'])) ?> · TB <?= $row['diemTrungBinh'] === null ? '—' : e(number_format((float)$row['diemTrungBinh'],2)) ?></small></span><div><i style="width:<?= $pc ?>%"></i></div><strong><?= $done ?>/<?= $total ?></strong></div>
  <?php endforeach; ?><?php if (!$g['theoMon']): ?><p class="muted">Chưa có bài thi để thống kê.</p><?php endif; ?></div>
 </section>
 <section class="panel"><div class="panel-heading"><div><h2>Truy cập nhanh</h2><p>Nghiệp vụ dành cho Hội đồng chấm thi</p></div></div><?php require __DIR__.'/dashboard_quick_links.php'; ?></section>
</div>

<section class="panel grading-history-panel"><div class="panel-heading"><div><h2>Điều chỉnh điểm gần nhất</h2><p>Lịch sử được ghi tự động khi cập nhật kết quả chấm thi</p></div><a class="text-link" href="<?= e(url('grading/lich-su.php')) ?>">Xem lịch sử →</a></div>
 <div class="table-scroll"><table class="data-table"><thead><tr><th>Mã phách</th><th>Môn thi</th><th>Điểm cũ</th><th>Điểm mới</th><th>Người sửa</th><th>Thời gian</th></tr></thead><tbody>
 <?php foreach ($g['ganDay'] as $row): ?><tr><td><span class="code-tag"><?= e($row['soPhach']) ?></span></td><td><?= e($row['tenMonThi']) ?></td><td><?= e(number_format((float)$row['diemTruocChinhSua'],2)) ?></td><td><strong><?= e(number_format((float)$row['diemSauChinhSua'],2)) ?></strong></td><td><?= e($row['nguoiThucHien']) ?></td><td><?= e(date('d/m/Y H:i',strtotime($row['thoiGianChinhSua']))) ?></td></tr><?php endforeach; ?>
 <?php if (!$g['ganDay']): ?><tr><td colspan="6" class="muted ui-align-center">Chưa có lịch sử chỉnh sửa điểm.</td></tr><?php endif; ?>
 </tbody></table></div>
</section>

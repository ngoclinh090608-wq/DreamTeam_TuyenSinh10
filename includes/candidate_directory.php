<?php
/** Shared read-only directory controller/template. Caller supplies the protected module key. */
require_once __DIR__ . '/auth.php';
$user = require_module($directoryModule);
require_once dirname(__DIR__) . '/services/candidate_directory.php';
$wishMode = $directoryModule === 'aspirations';
$isStudent = has_role($user, ['HOC_SINH']);
$owner = $isStudent ? (string)($user['maThiSinh'] ?? '') : null;
$getText = fn(string $key): string => is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
$query = mb_substr($getText('q'), 0, 120); $school = mb_substr($getText('school'), 0, 20);
$page = max(1, (int)filter_var($getText('page'), FILTER_VALIDATE_INT));
$pageSize = (int)$getText('per_page');
if (!in_array($pageSize, [25,50,100], true)) $pageSize = 25;
$data = null; $wishes = [];
try {
    $data = candidate_directory_page($query, $school, $page, $pageSize, $owner);
    if ($wishMode) $wishes = candidate_directory_wishes(array_column($data['rows'], 'maThiSinh'));
} catch (Throwable $exception) { error_log('candidate_directory: ' . $exception->getMessage()); }
$page_title = $wishMode ? 'Danh sách nguyện vọng' : 'Danh sách thí sinh'; $layout = 'dashboard'; $active = $directoryModule;
require __DIR__ . '/header.php';
?>
<div class="ui-page">
 <div class="page-head"><div><div class="eyebrow"><span></span><?= $wishMode ? 'NGUYỆN VỌNG TUYỂN SINH' : 'HỒ SƠ THÍ SINH' ?></div><h1><?= e($page_title) ?></h1><p><?= $isStudent ? 'Thông tin nguyện vọng gắn với tài khoản học sinh của bạn.' : 'Tra cứu theo mã, họ tên, số báo danh hoặc trường THCS. Dữ liệu được đọc từ cơ sở dữ liệu đang kết nối.' ?></p></div></div>
 <?php if (!$data): ?>
 <div class="ui-notice ui-notice-error" role="alert"><strong>Chưa tải được dữ liệu. Hãy kiểm tra kết nối database và hướng dẫn cài đặt; trang này không thay dữ liệu bằng danh sách minh họa.</strong></div>
 <?php else: ?>
 <section class="panel" aria-label="Bộ lọc thí sinh"><form method="get" class="ui-search">
  <div class="field"><label for="directory-query">Tìm kiếm</label><input id="directory-query" name="q" value="<?= e($query) ?>" placeholder="Mã, họ tên, SBD hoặc tên trường" maxlength="120"></div>
  <div class="field"><label for="directory-school">Trường THCS</label><select id="directory-school" name="school"><option value="">Tất cả trường</option><?php foreach ($data['schools'] as $item): ?><option value="<?= e($item['maTruongTHCS']) ?>"<?= $school === $item['maTruongTHCS'] ? ' selected' : '' ?>><?= e($item['tenTruongTHCS']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="directory-size">Số dòng mỗi trang</label><select id="directory-size" name="per_page"><?php foreach ([25,50,100] as $size): ?><option value="<?= $size ?>"<?= $pageSize === $size ? ' selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></div>
  <div class="ui-actions"><button class="btn btn-primary" type="submit">Tìm kiếm</button><a class="btn btn-outline" href="<?= e(url($directoryModule . '/')) ?>">Bỏ lọc</a></div>
 </form></section>
 <section class="panel table-panel ui-mt" aria-label="Kết quả tra cứu">
  <div class="table-toolbar"><div class="table-title"><h2><?= e($page_title) ?></h2><p><strong data-directory-total><?= (int)$data['filtered'] ?></strong> kết quả phù hợp · Trang <?= $data['page'] ?>/<?= $data['pages'] ?></p></div></div>
  <div class="table-scroll" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table"><thead><tr><th scope="col">STT</th><th scope="col">Mã thí sinh</th><th scope="col">Họ và tên</th><th scope="col">Số báo danh</th><th scope="col">Trường THCS</th><?php if ($wishMode): ?><th scope="col">Nguyện vọng 1</th><th scope="col">Nguyện vọng 2</th><th scope="col">Nguyện vọng 3</th><?php else: ?><th scope="col">Ngày sinh</th><th scope="col">Trạng thái</th><?php endif; ?></tr></thead><tbody>
   <?php foreach ($data['rows'] as $i => $row): ?><tr data-directory-row><td><?= $data['offset'] + $i + 1 ?></td><td><span class="code-tag"><?= e($row['maThiSinh']) ?></span></td><td><?= e($row['hoTen']) ?></td><td><?= e($row['soBaoDanh'] ?? '—') ?></td><td><?= e($row['tenTruongTHCS']) ?></td><?php if ($wishMode): ?><?php for ($choice = 1; $choice <= 3; $choice++): ?><td><?= e($wishes[$row['maThiSinh']][$choice] ?? 'Chưa có nguyện vọng') ?></td><?php endfor; ?><?php else: ?><td><?= e(date('d/m/Y', strtotime($row['ngaySinh']))) ?></td><td><?= e($row['trangThaiDangKyDuThi']) ?></td><?php endif; ?></tr><?php endforeach; ?>
   <?php if (!$data['rows']): ?><tr><td colspan="<?= $wishMode ? 8 : 7 ?>" class="muted">Không có dữ liệu phù hợp.</td></tr><?php endif; ?>
  </tbody></table></div>
  <?php $pageUrl = fn(int $number): string => url($directoryModule . '/') . '?' . http_build_query(['q'=>$query,'school'=>$school,'per_page'=>$pageSize,'page'=>$number]); ?>
  <nav class="table-bottom directory-pagination" aria-label="Phân trang"><span>Hiển thị <?= $data['rows'] ? $data['offset'] + 1 : 0 ?>–<?= $data['offset'] + count($data['rows']) ?> / <?= $data['filtered'] ?></span><div class="ui-actions"><?php if ($data['page'] > 1): ?><a class="btn btn-outline btn-sm" rel="prev" href="<?= e($pageUrl($data['page'] - 1)) ?>">← Trang trước</a><?php endif; ?><?php if ($data['page'] < $data['pages']): ?><a class="btn btn-outline btn-sm" rel="next" href="<?= e($pageUrl($data['page'] + 1)) ?>">Trang sau →</a><?php endif; ?></div></nav>
 </section>
 <?php if (!$isStudent): ?><section class="panel ui-mt"><div class="panel-heading"><div><h2>Số học sinh theo trường THCS</h2><p>Tổng số trong database, không phụ thuộc bộ lọc tìm kiếm.</p></div></div><div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">Mã trường</th><th scope="col">Trường THCS</th><th scope="col">Số học sinh</th></tr></thead><tbody><?php foreach ($data['schools'] as $item): ?><tr><td><?= e($item['maTruongTHCS']) ?></td><td><?= e($item['tenTruongTHCS']) ?></td><td><?= (int)$item['soHocSinh'] ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
 <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__.'/test_database.php';
require_test_database();
require_once dirname(__DIR__).'/services/public_score_service.php';
$p=db();$checks=0;
function lookup_check(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo "PASS $label\n";}
$row=$p->query("SELECT t.soBaoDanh,t.ngaySinh,t.maThiSinh FROM ThiSinh t JOIN BaiThi b ON b.maThiSinh=t.maThiSinh JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi WHERE t.soBaoDanh IS NOT NULL AND g.trangThaiCongBo='Đã công bố' LIMIT 1")->fetch();
if(!$row)throw new RuntimeException('Published QA fixture required');
$scores=public_published_scores($row['soBaoDanh'],$row['ngaySinh']);
lookup_check(count($scores)>0,'anonymous lookup returns published scores');
lookup_check(array_keys($scores[0])===['tenKyThi','tenMonThi','diemThi'],'lookup exposes no student profile or internal IDs');
lookup_check(public_published_scores($row['soBaoDanh'],'1900-01-01')===[],'wrong birth date reveals no scores');
lookup_check(public_published_scores('MISSING_QA_SBD',$row['ngaySinh'])===[],'unknown number reveals no scores');
foreach ([['','2011-01-01'],["' OR 1=1",'2011-01-01'],[$row['soBaoDanh'],'2011-02-30']] as [$number,$birth]) {
    try {public_published_scores($number,$birth);throw new LogicException('Invalid lookup accepted');}
    catch(BusinessException $e){lookup_check(true,'invalid number/date rejected');}
}
$p->beginTransaction();
try {
    $q=$p->prepare("UPDATE KetQuaChamThi g JOIN BaiThi b ON b.maBaiThi=g.maBaiThi SET g.trangThaiCongBo='Chưa công bố' WHERE b.maThiSinh=?");$q->execute([$row['maThiSinh']]);
    lookup_check(public_published_scores($row['soBaoDanh'],$row['ngaySinh'])===[],'unpublished scores never exposed');
} finally {$p->rollBack();}
passthru('python3 '.escapeshellarg(__DIR__.'/kiem_thu_public_scores_http.py'),$status);
lookup_check($status===0,'anonymous HTTP: CSRF, lookup and unchanged private routes');
echo "Public scores: $checks checks passed.\n";

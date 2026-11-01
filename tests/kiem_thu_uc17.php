<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/admission_service.php';
if (getenv('DB_NAME') !== 'dreamteam_tuyensinh10') throw new RuntimeException('Chỉ chạy trên database kiểm thử dreamteam_tuyensinh10, không chạy trên demo.');
$checks = 0;
function check17(bool $condition, string $label): void { global $checks; if (!$condition) throw new RuntimeException($label); $checks++; echo "PASS $label\n"; }
function rejects17(callable $fn, string $label): void { try { $fn(); } catch (BusinessException $e) { check17(true, $label); return; } throw new RuntimeException('Expected rejection: ' . $label); }
$pdo = db();
$actor = ['vaiTro'=>'HD_TUYEN_SINH','maTaiKhoan'=>'TEST_UC17'];
$options = ['closed'=>true,'appealsEnded'=>true,'weights'=>['TOAN'=>1,'VAN'=>1,'NN'=>1], 'bonuses'=>[], 'allocation'=>['U17_A'=>[1=>1,2=>0,3=>0],'U17_B'=>[1=>0,2=>1,3=>0],'U17_C'=>[1=>0,2=>0,3=>1]],'ties'=>[]];
$insert = static function(string $table, array $values) use ($pdo): void { $columns=array_keys($values); $stmt=$pdo->prepare('INSERT INTO '.$table.' ('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');$stmt->execute(array_values($values)); };
if ((int)$pdo->query("SELECT COUNT(*) FROM DotTuyenSinh WHERE maDot='U17_DOT'")->fetchColumn() > 0
    || (int)$pdo->query("SELECT COUNT(*) FROM ThiSinh WHERE maThiSinh LIKE 'U17_TS%'")->fetchColumn() > 0
    || (int)$pdo->query("SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema=DATABASE() AND trigger_name='u17_fail'")->fetchColumn() > 0) throw new RuntimeException('Fixture UC17 đã tồn tại; không ghi đè dữ liệu.');
try {
    $insert('DotTuyenSinh',['maDot'=>'U17_DOT','tenDot'=>'Test UC17','namTuyenSinh'=>2091]);
    $insert('KyThi',['maKyThi'=>'U17_EXAM','tenKyThi'=>'Test UC17','thoiGianKetThuc'=>'2020-01-01 00:00:00','maDot'=>'U17_DOT']);
    $insert('QuyDinhNguyenVong',['maQuyDinh'=>'U17_REG','namTuyenSinh'=>2091,'soLuongNguyenVongToiDa'=>3,'thoiGianBatDau'=>'2019-01-01','thoiGianKetThuc'=>'2020-01-01']);
    foreach (['A','B','C'] as $school) {
        $insert('TruongTHPT',['maTruongTHPT'=>'U17_'.$school,'tenTruongTHPT'=>'Test '.$school,'maKhuVuc'=>'KV01']);
        $insert('ThongTinTuyenSinh',['maThongTin'=>'U17_INFO_'.$school,'chiTieuTuyenSinh'=>1,'heDaoTao'=>'Công lập','phuongThucTuyenSinh'=>'Thi tuyển','trangThaiCongBo'=>'Đã công bố','maTruongTHPT'=>'U17_'.$school,'maDot'=>'U17_DOT']);
    }
    for ($i=1;$i<=4;$i++) {
        $id='U17_TS'.$i;
        $insert('ThiSinh',['maThiSinh'=>$id,'maHoSo'=>'U17_HS'.$i,'hoTen'=>'Test '.$i,'ngaySinh'=>'2010-01-01','dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','maTruongTHCS'=>'THCS_A']);
        foreach (['A','B','C'] as $j=>$school) $insert('NguyenVong',['maNguyenVong'=>'U17_NV'.$i.$school,'maThiSinh'=>$id,'maTruongTHPT'=>'U17_'.$school,'maQuyDinh'=>'U17_REG','thuTuNguyenVong'=>$j+1]);
        foreach (['TOAN','VAN','NN'] as $subject) {
            $paper='U17_B'.$i.$subject;
            $insert('BaiThi',['maBaiThi'=>$paper,'maKyThi'=>'U17_EXAM','maMonThi'=>$subject,'maThiSinh'=>$id,'trangThaiCham'=>'Đã chấm']);
            $insert('KetQuaChamThi',['maKetQuaChamThi'=>'U17_K'.$i.$subject,'maBaiThi'=>$paper,'diemThi'=>10-$i,'nguoiNhap'=>'Test UC17','trangThaiCongBo'=>'Đã công bố']);
        }
    }
    $command = 'python3 ' . escapeshellarg(__DIR__ . '/kiem_thu_uc17_http.py');
    passthru($command, $httpStatus);
    check17($httpStatus === 0, 'HTTP preview and permission checks');
    check17((int)$pdo->query("SELECT COUNT(*) FROM KetQuaXetTuyen WHERE maThiSinh LIKE 'U17_TS%'")->fetchColumn() === 0, 'HTTP preview does not persist results');
    $data=uc17_snapshot($pdo);
    $plan=uc17_plan($data,'U17_EXAM',$options);
    $winners=array_column(array_filter($plan['rows'],fn($r)=>$r['trangThai']==='Trúng tuyển'),'thuTuNguyenVong','maThiSinh');
    check17($winners===['U17_TS1'=>1,'U17_TS2'=>2,'U17_TS3'=>3], 'NV1, NV2, NV3; no student accepted twice');
    check17($plan['accepted']===3 && $plan['used']===['U17_A'=>1,'U17_B'=>1,'U17_C'=>1], 'Independent allocations and school capacities');
    $weighted=$options; $weighted['weights']['TOAN']=2; $weighted['bonuses']['U17_TS1']=['priority'=>1.5,'encouragement'=>0.5];
    check17(uc17_plan($data,'U17_EXAM',$weighted)['rows'][0]['diem']==38.0, 'Weights and priority/encouragement points');
    $tie=$data; foreach ($tie['KetQuaChamThi'] as &$g) if (str_starts_with($g['maKetQuaChamThi'],'U17_K2')) $g['diemThi']=9; unset($g);
    check17(uc17_plan($tie,'U17_EXAM',$options)['unresolved'], 'Unresolved boundary ties cannot be confirmed');
    $all=$options; $all['ties']['U17_A'][1]='all'; $p=uc17_plan($tie,'U17_EXAM',$all);
    check17($p['cutoffs']['U17_A'][1]['count']===2 && !$p['unresolved'], 'Tie A accepts every candidate at boundary');
    $higher=$options; $higher['ties']['U17_A'][1]='higher'; $p=uc17_plan($tie,'U17_EXAM',$higher);
    check17($p['cutoffs']['U17_A'][1]['count']===0, 'Tie B excludes entire boundary group, allows shortage');
    $bad=$options; $bad['closed']=false; rejects17(fn()=>uc17_plan($data,'U17_EXAM',$bad),'Unclosed official scores blocked');
    $bad=$options; $bad['appealsEnded']=false; rejects17(fn()=>uc17_plan($data,'U17_EXAM',$bad),'Appeal period must end');
    $bad=$options; $bad['allocation']['U17_A'][2]=1; rejects17(fn()=>uc17_plan($data,'U17_EXAM',$bad),'Invalid allocation sum blocked');
    $missing=$data; $missing['KetQuaChamThi']=array_values(array_filter($missing['KetQuaChamThi'],fn($g)=>$g['maKetQuaChamThi']!=='U17_K1TOAN')); rejects17(fn()=>uc17_plan($missing,'U17_EXAM',$options),'Missing score blocked');
    $pending=$data; $pending['YeuCauPhucKhao'][]=['maYeuCau'=>'U17_PK','maBaiThi'=>'U17_B1TOAN']; rejects17(fn()=>uc17_plan($pending,'U17_EXAM',$options),'Pending appeal blocked');
    $pending['KetQuaPhucKhao'][]=['maYeuCau'=>'U17_PK','diemSauPhucKhao'=>9.5];
    check17(uc17_plan($pending,'U17_EXAM',$options)['rows'][0]['diem']===27.5, 'Completed appeal score overrides original score');
    $invalid=$data; foreach ($invalid['NguyenVong'] as &$w) if ($w['maNguyenVong']==='U17_NV1A') $w['thuTuNguyenVong']=3; unset($w); rejects17(fn()=>uc17_plan($invalid,'U17_EXAM',$options),'Invalid wish order blocked');
    $bad=$options; $bad['weights']['TOAN']=0; rejects17(fn()=>uc17_plan($data,'U17_EXAM',$bad),'Zero coefficient blocked');
    $bad=$options; $bad['bonuses']['U17_TS1']=['priority'=>-1]; rejects17(fn()=>uc17_plan($data,'U17_EXAM',$bad),'Negative bonus blocked');
    $bad=$options; $bad['bonuses']['NOT_IN_ROUND']=['priority'=>1]; rejects17(fn()=>uc17_plan($data,'U17_EXAM',$bad),'Foreign candidate bonus blocked');
    $bad=$data; foreach ($bad['NguyenVong'] as &$w) if ($w['maNguyenVong']==='U17_NV1A') $w['lopChuyen']='Chuyên Toán'; unset($w); rejects17(fn()=>uc17_plan($bad,'U17_EXAM',$options),'Unsupported specialized wishes explicitly blocked');
    $bad=$data; foreach ($bad['KetQuaChamThi'] as &$g) if ($g['maKetQuaChamThi']==='U17_K1TOAN') $g['trangThaiCongBo']='Chưa công bố'; unset($g); rejects17(fn()=>uc17_plan($bad,'U17_EXAM',$options),'Unpublished official score blocked');
    $preview=uc17_preview('U17_EXAM',$options);
    rejects17(fn()=>uc17_save(['vaiTro'=>'HOC_SINH'],'U17_EXAM',$preview['fingerprint'],$options),'Non-council cannot save');
    $pdo->exec("UPDATE KetQuaChamThi SET diemThi=9.1 WHERE maKetQuaChamThi='U17_K1TOAN'");
    rejects17(fn()=>uc17_save($actor,'U17_EXAM',$preview['fingerprint'],$options),'Changed data invalidates preview');
    $pdo->exec("UPDATE KetQuaChamThi SET diemThi=9 WHERE maKetQuaChamThi='U17_K1TOAN'");
    $pdo->exec("CREATE TRIGGER u17_fail BEFORE INSERT ON KetQuaTuyenSinh FOR EACH ROW BEGIN IF NEW.maKetQua LIKE 'L17_%' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='UC17 rollback test'; END IF; END");
    $preview=uc17_preview('U17_EXAM',$options);
    try { uc17_save($actor,'U17_EXAM',$preview['fingerprint'],$options); throw new RuntimeException('Rollback not triggered'); } catch (PDOException $e) { check17(str_contains($e->getMessage(),'UC17 rollback test'),'Database failure surfaced'); }
    $pdo->exec('DROP TRIGGER u17_fail');
    check17((int)$pdo->query("SELECT COUNT(*) FROM KetQuaXetTuyen WHERE maThiSinh LIKE 'U17_TS%'")->fetchColumn()===0, 'Rollback leaves no partial results');
    $preview=uc17_preview('U17_EXAM',$options);$list=uc17_save($actor,'U17_EXAM',$preview['fingerprint'],$options);
    check17((int)$pdo->query("SELECT COUNT(*) FROM KetQuaXetTuyen WHERE maThiSinh LIKE 'U17_TS%' AND trangThaiXetTuyen='Trúng tuyển'")->fetchColumn()===3,'Official results saved');
    check17((int)$pdo->query("SELECT COUNT(*) FROM KetQuaTuyenSinh WHERE maDanhSach='$list'")->fetchColumn()===12,'All wishes linked for UC22');
    check17($pdo->query("SELECT trangThaiNguyenVong FROM NguyenVong WHERE maNguyenVong='U17_NV2B'")->fetchColumn()==='Trúng tuyển NV2','Wish status records winning priority');
    $before=$pdo->query("SELECT maKetQua FROM KetQuaXetTuyen WHERE maNguyenVong='U17_NV1A'")->fetchColumn();
    $preview=uc17_preview('U17_EXAM',$options); uc17_save($actor,'U17_EXAM',$preview['fingerprint'],$options);
    check17($pdo->query("SELECT maKetQua FROM KetQuaXetTuyen WHERE maNguyenVong='U17_NV1A'")->fetchColumn()===$before,'Rerun preserves result identifiers');
    putenv('UC17_HTTP_SAVE=1');
    passthru($command, $httpStatus);
    putenv('UC17_HTTP_SAVE');
    check17($httpStatus === 0, 'HTTP confirmed save and duplicate rejection');
    $pdo->exec("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Đã phê duyệt' WHERE maDanhSach='$list'");
    $preview=uc17_preview('U17_EXAM',$options);rejects17(fn()=>uc17_save($actor,'U17_EXAM',$preview['fingerprint'],$options),'Approved results cannot be overwritten');
    check17((int)$pdo->query("SELECT COUNT(*) FROM XetTuyenLanChay WHERE maKyThi='U17_EXAM'")->fetchColumn()===3,'Only committed runs audited');
} finally {
    $pdo->exec('DROP TRIGGER IF EXISTS u17_fail');
    $pdo->exec("DELETE FROM XetTuyenLanChay WHERE maKyThi='U17_EXAM'");
    $pdo->exec("DELETE l FROM KetQuaTuyenSinh l JOIN KetQuaXetTuyen x ON x.maKetQua=l.maKetQuaXetTuyen WHERE x.maThiSinh LIKE 'U17_TS%'");
    $pdo->exec("DELETE FROM DanhSachKetQuaTuyenSinh WHERE maDot='U17_DOT'");
    $pdo->exec("DELETE FROM KetQuaXetTuyen WHERE maThiSinh LIKE 'U17_TS%'");
    $pdo->exec("DELETE FROM KetQuaChamThi WHERE maKetQuaChamThi LIKE 'U17_K%'");
    $pdo->exec("DELETE FROM BaiThi WHERE maKyThi='U17_EXAM'");
    $pdo->exec("DELETE FROM NguyenVong WHERE maQuyDinh='U17_REG'");
    $pdo->exec("DELETE FROM ThiSinh WHERE maThiSinh LIKE 'U17_TS%'");
    $pdo->exec("DELETE FROM ThongTinTuyenSinh WHERE maDot='U17_DOT'");
    $pdo->exec("DELETE FROM TruongTHPT WHERE maTruongTHPT IN ('U17_A','U17_B','U17_C')");
    $pdo->exec("DELETE FROM QuyDinhNguyenVong WHERE maQuyDinh='U17_REG'");
    $pdo->exec("DELETE FROM KyThi WHERE maKyThi='U17_EXAM'");
    $pdo->exec("DELETE FROM DotTuyenSinh WHERE maDot='U17_DOT'");
}
echo "UC17: $checks checks passed.\n";

<?php
declare(strict_types=1);
require_once __DIR__ . '/test_database.php';
require_test_database();
require_once dirname(__DIR__) . '/services/candidate_number_service.php';
require_once dirname(__DIR__) . '/services/appeal_service.php';
require_once dirname(__DIR__) . '/services/grading_service.php';
require_once dirname(__DIR__) . '/services/admission_publication_service.php';
require_once dirname(__DIR__) . '/services/enrollment_update_service.php';
$p = db(); $checks = 0;
function qa_check(bool $ok, string $label): void { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; echo "PASS $label\n"; }
function qa_reject(callable $fn, string $label): void { try { $fn(); } catch (BusinessException $e) { qa_check(!db()->inTransaction(), $label); return; } throw new RuntimeException('Expected rejection: '.$label); }
function qa_insert(string $table, array $values): void { $q=db()->prepare('INSERT INTO '.$table.' ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_fill(0,count($values),'?')).')');$q->execute(array_values($values)); }
$admin = ['vaiTro'=>'BAN_TUYEN_SINH'];
$a=['vaiTro'=>'HOC_SINH','maThiSinh'=>'QA_A']; $b=['vaiTro'=>'HOC_SINH','maThiSinh'=>'QA_B'];
$staff=['vaiTro'=>'HD_PHUC_KHAO','maCanBo'=>'QA_CB','hoTen'=>'QA Council'];
$zone = new DateTimeZone('Asia/Ho_Chi_Minh'); $now = new DateTimeImmutable('now',$zone);
$yesterday = $now->modify('-1 day')->format('Y-m-d H:i:s');
qa_check((int)$p->query("SELECT COUNT(*) FROM ThiSinh WHERE maThiSinh IN ('QA_A','QA_B','QA_C','QA_D','QA_E')")->fetchColumn()===0,'fresh QA fixtures');
try {
    foreach (['A','B','C','D','E'] as $id) qa_insert('ThiSinh',['maThiSinh'=>'QA_'.$id,'maHoSo'=>'QA_HS_'.$id,'hoTen'=>'QA Student '.$id,'ngaySinh'=>'2011-01-01','maTruongTHCS'=>'THCS_A','dieuKienDuThi'=>$id==='C'?0:1,'trangThaiDangKyDuThi'=>$id==='C'?'Chưa duyệt':'Đã duyệt']);
    qa_reject(fn()=>candidate_issue_numbers($a,['QA_A']),'UC03 student denied');
    qa_reject(fn()=>candidate_issue_numbers($admin,[]),'UC03 empty selection denied');
    qa_reject(fn()=>candidate_issue_numbers($admin,['QA_A','QA_C']),'UC03 mixed eligibility atomic');
    qa_check($p->query("SELECT soBaoDanh FROM ThiSinh WHERE maThiSinh='QA_A'")->fetchColumn()===null,'UC03 no partial number');
    $p->exec("CREATE TRIGGER qa_number_fail BEFORE UPDATE ON ThiSinh FOR EACH ROW BEGIN IF NEW.maThiSinh='QA_B' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='QA number failure'; END IF; END");
    try { candidate_issue_numbers($admin,['QA_A','QA_B']); throw new RuntimeException('No injected SBD error'); }
    catch (PDOException $e) { qa_check(str_contains($e->getMessage(),'QA number failure'),'UC03 injected DB error'); }
    finally { $p->exec('DROP TRIGGER qa_number_fail'); }
    qa_check((int)$p->query("SELECT COUNT(*) FROM ThiSinh WHERE maThiSinh IN ('QA_A','QA_B') AND soBaoDanh IS NOT NULL")->fetchColumn()===0,'UC03 batch rollback');
    $numbers = candidate_issue_numbers($admin,['QA_A','QA_B']);
    qa_check(count(array_unique($numbers))===2 && $p->query("SELECT soBaoDanh FROM ThiSinh WHERE maThiSinh='QA_A'")->fetchColumn()===$numbers['QA_A'],'UC03 numbers unique and persisted');
    qa_reject(fn()=>candidate_issue_numbers($admin,['QA_A']),'UC03 repeat cannot overwrite');
    qa_check($p->query("SELECT soBaoDanh FROM ThiSinh WHERE maThiSinh='QA_A'")->fetchColumn()===$numbers['QA_A'],'UC03 original number preserved');

    qa_check(!appeal_window('2026-11-09 12:00:00',new DateTimeImmutable('2026-11-09 23:59:59',$zone))['open'],'UC18 same publication day closed');
    qa_check(appeal_window('2026-11-09 12:00:00',new DateTimeImmutable('2026-11-10 00:00:00',$zone))['open'],'UC18 opens next day midnight');
    qa_check(appeal_window('2026-11-09 12:00:00',new DateTimeImmutable('2026-11-16 23:59:59',$zone))['open'],'UC18 inclusive seventh day');
    qa_check(!appeal_window('2026-11-09 12:00:00',new DateTimeImmutable('2026-11-17 00:00:00',$zone))['open'],'UC18 eighth day closed');
    qa_check(!appeal_window(null)['open'],'UC18 unknown publication time closed');
    qa_check(!appeal_window('0000-00-00 00:00:00')['open'],'UC18 invalid legacy date fails closed');
    qa_check(appeal_window('2026-12-31 23:59:59',new DateTimeImmutable('2027-01-01 00:00:00',$zone))['open'],'UC18 calendar window crosses year');
    qa_insert('DotTuyenSinh',['maDot'=>'QA_DOT','tenDot'=>'QA admission','namTuyenSinh'=>2090]);
    qa_insert('KyThi',['maKyThi'=>'QA_EXAM','tenKyThi'=>'QA exam','thoiGianKetThuc'=>'2020-01-01 12:00:00','maDot'=>'QA_DOT']);
    qa_insert('HoiDongThi',['maHoiDongThi'=>'QA_HD','tenHoiDongThi'=>'QA appeal council','loaiHoiDong'=>'Phúc khảo','maKyThi'=>'QA_EXAM']);
    qa_insert('CanBo',['maCanBo'=>'QA_CB','hoTen'=>'QA Council','maHoiDongThi'=>'QA_HD']);
    foreach ([['A1','QA_A','TOAN'],['A2','QA_A','VAN'],['A3','QA_A','NN'],['B1','QA_B','TOAN']] as [$id,$owner,$subject]) {
        qa_insert('BaiThi',['maBaiThi'=>'QA_P'.$id,'maKyThi'=>'QA_EXAM','maMonThi'=>$subject,'maThiSinh'=>$owner,'trangThaiCham'=>'Đã chấm']);
        qa_insert('KetQuaChamThi',['maKetQuaChamThi'=>'QA_G'.$id,'maBaiThi'=>'QA_P'.$id,'diemThi'=>7,'nguoiNhap'=>'QA','trangThaiCongBo'=>$id==='A3'?'Chưa công bố':'Đã công bố','thoiDiemCongBo'=>$id==='A3'?null:$yesterday]);
    }
    foreach ([['qa_student_a',$a['maThiSinh'],null,'HOC_SINH'],['qa_student_b',$b['maThiSinh'],null,'HOC_SINH'],['qa_council',null,'QA_CB','HD_PHUC_KHAO']] as $i=>[$login,$owner,$cb,$role]) qa_insert('TaiKhoan',['maTaiKhoan'=>'QA_TK'.$i,'tenDangNhap'=>$login,'matKhau'=>password_hash('qa-test-password',PASSWORD_DEFAULT),'hoTen'=>$login,'vaiTro'=>$role,'maThiSinh'=>$owner,'maCanBo'=>$cb]);
    qa_reject(fn()=>appeal_submit($admin,'QA_PA1','Review'),'UC18 staff cannot submit');
    qa_reject(fn()=>appeal_submit($a,'QA_PB1','Review'),'UC18 ownership enforced');
    qa_reject(fn()=>appeal_submit($a,'QA_PA1',''),'UC18 reason required');
    qa_reject(fn()=>appeal_submit($a,'QA_PA3','Review'),'UC18 unpublished grade denied');
    $p->exec("UPDATE KetQuaChamThi SET thoiDiemCongBo=NULL WHERE maKetQuaChamThi='QA_GA1'");
    qa_reject(fn()=>appeal_submit($a,'QA_PA1','Review'),'UC18 legacy publication time not guessed');
    foreach ([$now->format('Y-m-d H:i:s'),$now->modify('-8 days')->format('Y-m-d H:i:s')] as $date) {
        $p->prepare("UPDATE KetQuaChamThi SET thoiDiemCongBo=? WHERE maKetQuaChamThi='QA_GA1'")->execute([$date]);
        qa_reject(fn()=>appeal_submit($a,'QA_PA1','Review'),'UC18 closed period rejected');
    }
    $p->prepare("UPDATE KetQuaChamThi SET thoiDiemCongBo=? WHERE maKetQuaChamThi='QA_GA1'")->execute([$yesterday]);
    $request=appeal_submit($a,'QA_PA1','Review total');
    qa_check(count(appeal_requests($a))===1 && appeal_requests($b)===[],'UC18 save and isolate requests');
    qa_check(appeal_requests($a,2)===[],'UC16/18 pagination does not repeat first page');
    qa_reject(fn()=>appeal_submit($a,'QA_PA1','Repeat'),'UC18 duplicate blocked');
    qa_reject(fn()=>appeal_complete($a,$request,'8','OK','scan','minutes'),'UC16 student cannot process');
    qa_reject(fn()=>appeal_complete(['vaiTro'=>'HD_PHUC_KHAO','maCanBo'=>'CB11'],$request,'8','OK','scan','minutes'),'UC16 different council denied');
    foreach (['-1','11','NaN','7.555','7.23'] as $score) qa_reject(fn()=>appeal_complete($staff,$request,$score,'OK','scan','minutes'),'UC16 invalid score '.$score);
    qa_reject(fn()=>appeal_complete($staff,$request,'8','','scan','minutes'),'UC16 conclusion required');
    $p->exec("CREATE TRIGGER qa_appeal_fail BEFORE INSERT ON LichSuChinhSuaDiem FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='QA audit failure'");
    try { appeal_complete($staff,$request,'9.25','Adjusted','scan','minutes');throw new RuntimeException('No injected appeal error'); }
    catch (PDOException $e) { qa_check(str_contains($e->getMessage(),'QA audit failure'),'UC16 injected audit failure'); }
    finally { $p->exec('DROP TRIGGER qa_appeal_fail'); }
    qa_check((float)$p->query("SELECT diemThi FROM KetQuaChamThi WHERE maKetQuaChamThi='QA_GA1'")->fetchColumn()===7.0 && (int)$p->query('SELECT COUNT(*) FROM KetQuaPhucKhao WHERE maYeuCau='.$p->quote($request))->fetchColumn()===0,'UC16 score/result/evidence/minutes rollback');
    qa_check($p->query('SELECT trangThai FROM YeuCauPhucKhao WHERE maYeuCau='.$p->quote($request))->fetchColumn()==='Chờ xử lý','UC16 request remains pending after error');
    appeal_complete($staff,$request,'9.25','Adjusted','scan checked','council minutes');
    $row=appeal_requests($a)[0];
    qa_check((float)$row['diemSauPhucKhao']===9.25 && $row['minhChung']==='scan checked' && $row['bienBan']==='council minutes','UC16 result/evidence/minutes persisted');
    qa_check((float)$p->query("SELECT diemThi FROM KetQuaChamThi WHERE maKetQuaChamThi='QA_GA1'")->fetchColumn()===9.25 && (int)$p->query("SELECT COUNT(*) FROM LichSuChinhSuaDiem WHERE maKetQuaChamThi='QA_GA1'")->fetchColumn()===1,'UC16 official score and history updated');
    qa_reject(fn()=>appeal_complete($staff,$request,'9.5','Repeat','scan','minutes'),'UC16 repeat completion denied');

    qa_insert('QuyDinhNguyenVong',['maQuyDinh'=>'QA_REG','namTuyenSinh'=>2090,'soLuongNguyenVongToiDa'=>3,'thoiGianBatDau'=>'2019-01-01','thoiGianKetThuc'=>'2020-01-01']);
    foreach (['A'=>'THPT_1','B'=>'THPT_2'] as $id=>$school) {
        qa_insert('NguyenVong',['maNguyenVong'=>'QA_NV'.$id,'maThiSinh'=>'QA_'.$id,'maTruongTHPT'=>$school,'maQuyDinh'=>'QA_REG','thuTuNguyenVong'=>1]);
        qa_insert('KetQuaXetTuyen',['maKetQua'=>'QA_R'.$id,'maThiSinh'=>'QA_'.$id,'maNguyenVong'=>'QA_NV'.$id,'diemXetTuyen'=>24,'trangThaiXetTuyen'=>'Trúng tuyển','maTruongTHPT'=>$school]);
    }
    qa_insert('DanhSachKetQuaTuyenSinh',['maDanhSach'=>'QA_LIST','namTuyenSinh'=>2090,'maDot'=>'QA_DOT']);
    foreach (['A','B'] as $id) qa_insert('KetQuaTuyenSinh',['maKetQua'=>'QA_L'.$id,'maDanhSach'=>'QA_LIST','maKetQuaXetTuyen'=>'QA_R'.$id]);
    qa_check(student_published_admissions($a)===[],'UC04 unpublished admission hidden');
    qa_check(count(student_published_scores($a))===2,'UC04 unpublished score hidden');
    $p->exec("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Đã phê duyệt',trangThaiCongBo='Đã công bố' WHERE maDanhSach='QA_LIST'");
    qa_check(array_column(student_published_admissions($a),'maKetQua')===['QA_RA'] && array_column(student_published_admissions($b),'maKetQua')===['QA_RB'],'UC04/19 published results ownership');
    qa_reject(fn()=>student_published_admissions(['vaiTro'=>'HOC_SINH']),'UC04 unbound student denied');
    qa_reject(fn()=>appeal_submit($a,'QA_PA2','After approval'),'UC18 approved admission blocks new request');
    $p->exec("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Chưa phê duyệt',trangThaiCongBo='Chưa công bố' WHERE maDanhSach='QA_LIST'");
    // HTTP helper uses these committed QA fixtures, then database assertions validate the writes.
    $command='python3 '.escapeshellarg(__DIR__.'/kiem_thu_qa_http.py');passthru($command,$status);
    qa_check($status===0,'UC03/04/16/18/19 HTTP and CSRF');
    qa_check((float)$p->query("SELECT diemThi FROM KetQuaChamThi WHERE maKetQuaChamThi='QA_GA2'")->fetchColumn()===8.5,'UC16 HTTP final score persisted');
    qa_check($p->query("SELECT soBaoDanh FROM ThiSinh WHERE maThiSinh='QA_D'")->fetchColumn()!==null,'UC03 HTTP issuance persisted');
    // Timestamp is recorded on actual UC15 publication and is immutable after it.
    $p->exec("UPDATE KetQuaChamThi SET trangThaiCongBo='Chưa công bố' WHERE maKetQuaChamThi IN ('QA_GA1','QA_GA2','QA_GB1')");
    capNhatTrangThaiCongBo('QA_EXAM','NN');
    qa_check($p->query("SELECT thoiDiemCongBo FROM KetQuaChamThi WHERE maKetQuaChamThi='QA_GA3'")->fetchColumn()!==null,'UC15 stores publication timestamp');
    $saved=$p->query("SELECT thoiDiemCongBo FROM KetQuaChamThi WHERE maKetQuaChamThi='QA_GA3'")->fetchColumn();
    $p->exec("UPDATE KetQuaChamThi SET trangThaiCongBo='Chưa công bố' WHERE maKetQuaChamThi='QA_GA3'");
    capNhatTrangThaiCongBo('QA_EXAM','NN');
    qa_check($p->query("SELECT thoiDiemCongBo FROM KetQuaChamThi WHERE maKetQuaChamThi='QA_GA3'")->fetchColumn()===$saved,'UC15 republish does not extend appeal deadline');
    capNhatTrangThaiCongBo('QA_EXAM','TOAN'); capNhatTrangThaiCongBo('QA_EXAM','VAN');
    $p->exec("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Đã phê duyệt' WHERE maDanhSach='QA_LIST'");
    try { uc22_congBo('QA_LIST'); throw new RuntimeException('UC22 accepted an open appeal period'); }
    catch (DomainException $e) { qa_check(str_contains($e->getMessage(),'phúc khảo'),'UC22 blocks publication during appeal period'); }
    // Advance the fixture to a completed seven-day period, without changing the system clock.
    $p->prepare("UPDATE KetQuaChamThi SET thoiDiemCongBo=? WHERE maKetQuaChamThi LIKE 'QA_G%'")->execute([$now->modify('-9 days')->format('Y-m-d H:i:s')]);
    $p->exec("CREATE TRIGGER qa_enrollment_fail BEFORE INSERT ON TinhTrangNhapHoc FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='QA enrollment failure'");
    try { uc22_congBo('QA_LIST'); throw new RuntimeException('Missing enrollment error'); }
    catch (PDOException $e) { qa_check(str_contains($e->getMessage(),'QA enrollment failure'),'UC22 enrollment insertion error surfaced'); }
    finally { $p->exec('DROP TRIGGER qa_enrollment_fail'); }
    qa_check($p->query("SELECT trangThaiCongBo FROM DanhSachKetQuaTuyenSinh WHERE maDanhSach='QA_LIST'")->fetchColumn()==='Chưa công bố' && (int)$p->query("SELECT COUNT(*) FROM TinhTrangNhapHoc WHERE maKetQua IN ('QA_RA','QA_RB')")->fetchColumn()===0,'UC22 publication and enrollment roll back together');
    uc22_congBo('QA_LIST');
    $enrollments=uc20_layDanhSach('QA_A');
    qa_check(count($enrollments)===1 && $enrollments[0]['trangThaiNhapHoc']==='Chưa xác nhận','UC22 published winner reaches UC20 without manual SQL');
    uc20_capNhatTinhTrang($enrollments[0]['maTinhTrang'],'Đã xác nhận','QA council');
    qa_check(student_published_admissions($a)[0]['trangThaiNhapHoc']==='Đã xác nhận','UC20 confirmation reaches student UC19');
    $p->exec("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiCongBo='Chưa công bố' WHERE maDanhSach='QA_LIST'");
    uc22_congBo('QA_LIST');
    qa_check(student_published_admissions($a)[0]['trangThaiNhapHoc']==='Đã xác nhận' && (int)$p->query("SELECT COUNT(*) FROM TinhTrangNhapHoc WHERE maKetQua IN ('QA_RA','QA_RB')")->fetchColumn()===2,'UC22 preserves existing confirmations without duplicate enrollment');
} finally {
    if($p->inTransaction())$p->rollBack();
    $p->exec('DROP TRIGGER IF EXISTS qa_number_fail');$p->exec('DROP TRIGGER IF EXISTS qa_appeal_fail');
    $p->exec('DROP TRIGGER IF EXISTS qa_enrollment_fail');
    $p->exec("DELETE mc FROM MinhChungPhucKhao mc JOIN KetQuaPhucKhao r ON r.maKetQua=mc.maKetQua JOIN YeuCauPhucKhao y ON y.maYeuCau=r.maYeuCau WHERE y.maThiSinh IN ('QA_A','QA_B')");
    $p->exec("DELETE bb FROM BienBanPhucKhao bb JOIN KetQuaPhucKhao r ON r.maKetQua=bb.maKetQua JOIN YeuCauPhucKhao y ON y.maYeuCau=r.maYeuCau WHERE y.maThiSinh IN ('QA_A','QA_B')");
    $p->exec("DELETE r FROM KetQuaPhucKhao r JOIN YeuCauPhucKhao y ON y.maYeuCau=r.maYeuCau WHERE y.maThiSinh IN ('QA_A','QA_B')");
    $p->exec("DELETE FROM YeuCauPhucKhao WHERE maThiSinh IN ('QA_A','QA_B')");
    $p->exec("DELETE FROM LichSuChinhSuaDiem WHERE maKetQuaChamThi LIKE 'QA_G%'");
    $p->exec("DELETE FROM KetQuaChamThi WHERE maKetQuaChamThi LIKE 'QA_G%'");
    $p->exec("DELETE FROM BaiThi WHERE maKyThi='QA_EXAM'");
    $p->exec("DELETE h FROM LichSuCapNhatNhapHoc h JOIN TinhTrangNhapHoc t ON t.maTinhTrang=h.maTinhTrang WHERE t.maKetQua IN ('QA_RA','QA_RB')");
    $p->exec("DELETE FROM TinhTrangNhapHoc WHERE maKetQua IN ('QA_RA','QA_RB')");
    $p->exec("DELETE FROM KetQuaTuyenSinh WHERE maDanhSach='QA_LIST'");$p->exec("DELETE FROM DanhSachKetQuaTuyenSinh WHERE maDanhSach='QA_LIST'");
    $p->exec("DELETE FROM KetQuaXetTuyen WHERE maKetQua IN ('QA_RA','QA_RB')");$p->exec("DELETE FROM NguyenVong WHERE maQuyDinh='QA_REG'");$p->exec("DELETE FROM QuyDinhNguyenVong WHERE maQuyDinh='QA_REG'");
    $p->exec("DELETE FROM TaiKhoan WHERE maTaiKhoan LIKE 'QA_TK%'");$p->exec("DELETE FROM CanBo WHERE maCanBo='QA_CB'");$p->exec("DELETE FROM HoiDongThi WHERE maHoiDongThi='QA_HD'");
    $p->exec("DELETE FROM KyThi WHERE maKyThi='QA_EXAM'");$p->exec("DELETE FROM DotTuyenSinh WHERE maDot='QA_DOT'");
    $p->exec("DELETE FROM ThiSinh WHERE maThiSinh IN ('QA_A','QA_B','QA_C','QA_D','QA_E')");
}
echo "QA regression: $checks assertions passed.\n";

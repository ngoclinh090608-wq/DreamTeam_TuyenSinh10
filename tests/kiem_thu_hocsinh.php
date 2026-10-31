<?php
require dirname(__DIR__) . '/services/live_data.php';
require dirname(__DIR__) . '/includes/functions.php';
$p=db();$p->beginTransaction();
try {
 $p->exec("INSERT INTO TinhTrangNhapHoc (maTinhTrang,maKetQua,trangThaiNhapHoc) VALUES ('CHECK_STUDENT_A','KQXT001','Chưa xác nhận'),('CHECK_STUDENT_B','KQXT004','Chưa xác nhận')");
 $a=live_rows('enrollment','TS001');$b=live_rows('enrollment','TS002');
 if(count($a)!==1 || $a[0][0]!=='TS001' || count($b)!==1 || $b[0][0]!=='TS002')throw new RuntimeException('Enrollment leaks');
 $a=live_rows('appeals','TS001');$b=live_rows('appeals','TS002');
 if(count($a)!==1 || $a[0][1]!=='TS001' || count($b)!==1 || $b[0][1]!=='TS002')throw new RuntimeException('Appeals leak');
 if(live_rows('appeals','')!==[] || live_rows('enrollment','')!==[] || live_student_dashboard('')!==null)throw new RuntimeException('Unbound account leaks');
 $p->exec("UPDATE ThongTinTuyenSinh SET trangThaiCongBo='Chưa công bố' WHERE maThongTin='TT_THPT_1'");
 foreach(live_rows('admission-info','TS001') as $r)if($r[0]==='TT_THPT_1')throw new RuntimeException('Unpublished admission info leaks');
 foreach(live_student_admission_info() as $r)if($r['maThongTin']==='TT_THPT_1')throw new RuntimeException('Unpublished admission card leaks');
 if(live_student_admission_info('%')!==[])throw new RuntimeException('Search wildcard must be literal');
 $personal = live_student_dashboard('TS001');
 $count = $p->query("SELECT COUNT(*) FROM BaiThi b JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi WHERE b.maThiSinh='TS001' AND k.trangThaiCongBo='Đã công bố'")->fetchColumn();
 if (!$personal || count($personal['scores']) !== (int)$count || $personal['results'] !== []) throw new RuntimeException('Student published results scope failed');
 $studentDashboard = $personal; $profile = $personal['profile'];
 ob_start(); require dirname(__DIR__) . '/includes/student_wish_progress.php'; $pendingHtml = ob_get_clean();
 if (str_contains($pendingHtml, 'is-rejected') || !str_contains($pendingHtml, 'Chưa công bố')) throw new RuntimeException('Unpublished result rendered as final');
 $p->exec("UPDATE KetQuaXetTuyen SET trangThaiXetTuyen='Trúng tuyển',maTruongTHPT='THPT_1' WHERE maKetQua='KQXT001'");
 $p->exec("INSERT INTO KetQuaTuyenSinh (maKetQua,maDanhSach,maKetQuaXetTuyen) VALUES ('CHECK_PUBLISHED','DS2026','KQXT001')");
 $p->exec("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Đã phê duyệt',trangThaiCongBo='Đã công bố' WHERE maDanhSach='DS2026'");
 $studentDashboard = live_student_dashboard('TS001'); $profile=$studentDashboard['profile'];
 if (count($studentDashboard['results'])!==1 || live_student_dashboard('TS002')['results']!==[]) throw new RuntimeException('Published results ownership failed');
 ob_start(); require dirname(__DIR__) . '/includes/student_wish_progress.php'; $acceptedHtml=ob_get_clean();
 if (!str_contains($acceptedHtml,'Trúng tuyển') || str_contains($acceptedHtml,'is-rejected')) throw new RuntimeException('Accepted progress failed');
 $p->exec("UPDATE KetQuaXetTuyen SET trangThaiXetTuyen='Không trúng tuyển' WHERE maKetQua='KQXT001'");
 $studentDashboard=live_student_dashboard('TS001');
 ob_start(); require dirname(__DIR__) . '/includes/student_wish_progress.php'; $rejectedHtml=ob_get_clean();
 if (substr_count($rejectedHtml,'class="is-rejected"')!==1 || !str_contains($rejectedHtml,'Không trúng tuyển')) throw new RuntimeException('Rejected progress must apply only to published preference');
 $sites=live_student_dashboard('TS001')['examSites'];
 $p->exec("INSERT INTO PhanPhongThi (maPhanPhong,maThiSinh,maPhong,trangThai) VALUES ('CHECK_CANCELLED','TS001','P201','Đã hủy')");
 if(live_student_dashboard('TS001')['examSites']!==$sites || live_student_dashboard('TS002')['profile']['maThiSinh']!=='TS002')throw new RuntimeException('Cancelled room or owner isolation failed');
 echo "PASS pending, accepted and rejected progress, published result ownership\n";
 echo "PASS two students isolated in appeals/enrollment, unbound student denied, unpublished notice hidden\n";
} finally {$p->rollBack();}

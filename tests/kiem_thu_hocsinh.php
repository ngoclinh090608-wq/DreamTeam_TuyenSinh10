<?php
require dirname(__DIR__) . '/services/live_data.php';
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
 echo "PASS two students isolated in appeals/enrollment, unbound student denied, unpublished notice hidden\n";
} finally {$p->rollBack();}

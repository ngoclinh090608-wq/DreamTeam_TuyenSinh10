<?php
declare(strict_types=1);
require dirname(__DIR__).'/services/student_profile.php';
$p=db();
if(!student_profile_ready())throw new RuntimeException('Student profile tables missing');
$account=$p->query("SELECT maTaiKhoan,maThiSinh,vaiTro FROM TaiKhoan WHERE tenDangNhap='SBD0001'")->fetch();
$owner=(string)$account['maThiSinh'];
foreach(['HoSoBoSung','GiaDinhThiSinh','HocTapTHCS'] as $table){$st=$p->prepare("SELECT COUNT(*) FROM $table WHERE maThiSinh=?");$st->execute([$owner]);if($st->fetchColumn())throw new RuntimeException('Use a fresh test database; existing supplementary data will not be overwritten');}
$input=['maThiSinh'=>'TS002','email'=>'student@example.test','gioiTinh'=>'Nữ','soDinhDanh'=>'123456789012','family'=>['Me'=>['hoTen'=>'Người thân kiểm thử','namSinh'=>'1980','dienThoai'=>'0900000001']], 'years'=>[6=>['diemTrungBinh'=>'8.25','hocLuc'=>'Tốt','hanhKiem'=>'Tốt']]];
try{
 student_profile_save($account,$input);
 $saved=student_profile_read($owner);
 if($saved['extra']['email']!==$input['email'] || $saved['family']['Me']['hoTen']!==$input['family']['Me']['hoTen'] || (float)$saved['schoolYears'][6]['diemTrungBinh']!==8.25)throw new RuntimeException('Supplementary data did not persist');
 if(student_profile_read('TS002')['extra']!==[])throw new RuntimeException('Posted ownership field was trusted');
 foreach([['email'=>'invalid'],['soDinhDanh'=>'123'],['years'=>[6=>['diemTrungBinh'=>'11']]],['family'=>['Me'=>['namSinh'=>'9999']]]] as $change){
  try{student_profile_save($account,array_replace($input,$change));throw new RuntimeException('Invalid data accepted');}catch(BusinessException $expected){}
 }
 try{student_profile_save(array_replace($account,['maThiSinh'=>'TS002']),$input);throw new RuntimeException('Mismatched account accepted');}catch(BusinessException $expected){}
 try{student_profile_save(array_replace($account,['vaiTro'=>'HD_CHAM_THI']),$input);throw new RuntimeException('Other role accepted');}catch(BusinessException $expected){}
 if(student_profile_read($owner)['extra']['email']!==$input['email'])throw new RuntimeException('Rejected input changed saved data');
 echo "PASS save/reload profile, family and grades; ownership, role and invalid-input checks\n";
}finally{
 foreach(['HocTapTHCS','GiaDinhThiSinh','HoSoBoSung'] as $table){$st=$p->prepare("DELETE FROM $table WHERE maThiSinh=?");$st->execute([$owner]);}
}

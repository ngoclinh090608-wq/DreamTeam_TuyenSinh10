<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/database.php';
require_once __DIR__.'/business_exception.php';

function student_profile_ready(): bool {
    $count=db()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND LOWER(table_name) IN ('hosobosung','giadinhthisinh','hoctapthcs')")->fetchColumn();
    return (int)$count===3;
}
function student_profile_read(string $owner): array {
    if (!student_profile_ready()) return ['ready'=>false,'extra'=>[],'family'=>[],'schoolYears'=>[]];
    $st=db()->prepare('SELECT * FROM HoSoBoSung WHERE maThiSinh=?');$st->execute([$owner]);$extra=$st->fetch() ?: [];
    $st=db()->prepare('SELECT * FROM GiaDinhThiSinh WHERE maThiSinh=?');$st->execute([$owner]);$family=[];
    foreach($st->fetchAll() as $row)$family[$row['quanHe']]=$row;
    $st=db()->prepare('SELECT * FROM HocTapTHCS WHERE maThiSinh=? ORDER BY lop');$st->execute([$owner]);$schoolYears=[];
    foreach($st->fetchAll() as $row)$schoolYears[$row['lop']]=$row;
    return ['ready'=>true,'extra'=>$extra,'family'=>$family,'schoolYears'=>$schoolYears];
}
function student_profile_text(array $input,string $key,int $max): string {
    $value=$input[$key] ?? '';
    if(!is_string($value) || mb_strlen(trim($value))>$max)throw new BusinessException('Thông tin nhập không hợp lệ hoặc quá dài: '.$key);
    return trim($value);
}
function student_profile_phone(string $value): void {
    if($value!=='' && !preg_match('/^\+?[0-9]{9,15}$/D',$value))throw new BusinessException('Số điện thoại cần có từ 9 đến 15 chữ số.');
}
function student_profile_save(array $account,array $input,?array $photo=null): void {
    if(($account['vaiTro'] ?? '')!=='HOC_SINH' || empty($account['maThiSinh']))throw new BusinessException('Tài khoản không được phép cập nhật hồ sơ học sinh.');
    if(!student_profile_ready())throw new BusinessException('Chưa có cấu trúc lưu hồ sơ bổ sung. Vui lòng liên hệ quản trị hệ thống.');
    $owner=(string)$account['maThiSinh'];$fields=['gioiTinh'=>10,'soDinhDanh'=>12,'noiSinh'=>150,'danToc'=>50,'quocTich'=>50,'tonGiao'=>50,'dienThoai'=>20,'email'=>150,'lop'=>20,'diaChiThuongTru'=>500,'choOHienNay'=>500,'doiTuongUuTien'=>250];$values=[];
    foreach($fields as $key=>$max)$values[$key]=student_profile_text($input,$key,$max);
    if(!in_array($values['gioiTinh'],['','Nam','Nữ','Khác'],true))throw new BusinessException('Giới tính không hợp lệ.');
    if($values['soDinhDanh']!=='' && !preg_match('/^[0-9]{12}$/D',$values['soDinhDanh']))throw new BusinessException('Số định danh/CCCD cần đủ 12 chữ số.');
    student_profile_phone($values['dienThoai']);
    if($values['email']!=='' && !filter_var($values['email'],FILTER_VALIDATE_EMAIL))throw new BusinessException('Email không hợp lệ.');
    $families=[];$years=[];
    foreach(['Me','Cha','GiamHo'] as $relation){
        $row=$input['family'][$relation] ?? [];if(!is_array($row))throw new BusinessException('Thông tin gia đình không hợp lệ.');
        $name=student_profile_text($row,'hoTen',100);$year=student_profile_text($row,'namSinh',4);$phone=student_profile_text($row,'dienThoai',20);student_profile_phone($phone);
        if($year!=='' && (!ctype_digit($year) || (int)$year<1900 || (int)$year>(int)date('Y')))throw new BusinessException('Năm sinh người thân không hợp lệ.');
        $families[]=[$owner,$relation,$name,$year===''?null:(int)$year,$phone];
    }
    foreach(range(6,9) as $grade){
        $row=$input['years'][$grade] ?? [];if(!is_array($row))throw new BusinessException('Thông tin học tập không hợp lệ.');
        $average=student_profile_text($row,'diemTrungBinh',5);$study=student_profile_text($row,'hocLuc',30);$conduct=student_profile_text($row,'hanhKiem',30);
        if($average!=='' && (!preg_match('/^(?:[0-9](?:\.[0-9]{1,2})?|10(?:\.0{1,2})?)$/D',$average)))throw new BusinessException('Điểm trung bình cần từ 0 đến 10, tối đa hai chữ số thập phân.');
        if(!in_array($study,['','Tốt','Khá','Đạt','Chưa đạt','Giỏi','Trung bình','Yếu','Kém'],true) || !in_array($conduct,['','Tốt','Khá','Đạt','Chưa đạt','Trung bình','Yếu'],true))throw new BusinessException('Xếp loại học tập/rèn luyện không hợp lệ.');
        $years[]=[$owner,$grade,$average===''?null:$average,$study,$conduct];
    }
    $newPhoto=null;$oldPhoto=null;
    if($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
        if(($photo['error'] ?? -1)!==UPLOAD_ERR_OK || ($photo['size'] ?? 0)>2*1024*1024 || !is_uploaded_file($photo['tmp_name'] ?? ''))throw new BusinessException('Ảnh hồ sơ không hợp lệ hoặc lớn hơn 2 MB.');
        $info=@getimagesize($photo['tmp_name']);$formats=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!$info || !isset($formats[$info['mime']]) || $info[0]>6000 || $info[1]>6000)throw new BusinessException('Chỉ nhận ảnh JPG, PNG hoặc WebP tối đa 6000×6000.');
        $newPhoto=bin2hex(random_bytes(16)).'.'.$formats[$info['mime']];
        $dir=dirname(__DIR__).'/storage/student-photos';
        if(!is_dir($dir) && !mkdir($dir,0700,true))throw new RuntimeException('Cannot create photo storage');
        if(!move_uploaded_file($photo['tmp_name'],$dir.'/'.$newPhoto))throw new RuntimeException('Cannot save student photo');
    }
    $pdo=db();$pdo->beginTransaction();
    try {
        $st=$pdo->prepare("SELECT maThiSinh FROM TaiKhoan WHERE maTaiKhoan=? AND maThiSinh=? AND vaiTro='HOC_SINH' AND trangThai='Hoạt động' FOR UPDATE");$st->execute([$account['maTaiKhoan'],$owner]);
        if(!$st->fetchColumn())throw new BusinessException('Tài khoản không có quyền ghi hồ sơ này.');
        $st=$pdo->prepare('SELECT anhHoSo FROM HoSoBoSung WHERE maThiSinh=? FOR UPDATE');$st->execute([$owner]);$oldPhoto=$st->fetchColumn() ?: null;
        $columns=array_keys($fields);$columns[]='anhHoSo';$args=array_values($values);$args[1]=$values['soDinhDanh']===''?null:$values['soDinhDanh'];$args[]=$newPhoto ?? $oldPhoto;
        $st=$pdo->prepare('INSERT INTO HoSoBoSung (maThiSinh,'.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns)+1,'?')).') ON DUPLICATE KEY UPDATE '.implode(',',array_map(fn($c)=>"$c=VALUES($c)",$columns)));$st->execute(array_merge([$owner],$args));
        $st=$pdo->prepare('INSERT INTO GiaDinhThiSinh (maThiSinh,quanHe,hoTen,namSinh,dienThoai) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE hoTen=VALUES(hoTen),namSinh=VALUES(namSinh),dienThoai=VALUES(dienThoai)');foreach($families as $row)$st->execute($row);
        $st=$pdo->prepare('INSERT INTO HocTapTHCS (maThiSinh,lop,diemTrungBinh,hocLuc,hanhKiem) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE diemTrungBinh=VALUES(diemTrungBinh),hocLuc=VALUES(hocLuc),hanhKiem=VALUES(hanhKiem)');foreach($years as $row)$st->execute($row);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($newPhoto)@unlink(dirname(__DIR__).'/storage/student-photos/'.$newPhoto);throw $e;}
    if($newPhoto && $oldPhoto && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$oldPhoto))@unlink(dirname(__DIR__).'/storage/student-photos/'.$oldPhoto);
}

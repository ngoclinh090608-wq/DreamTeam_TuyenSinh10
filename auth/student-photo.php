<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/services/student_profile.php';
$user=require_role(['HOC_SINH']);
$record=student_profile_read((string)($user['maThiSinh'] ?? ''));
$file=$record['extra']['anhHoSo'] ?? '';
if(!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$file)) {http_response_code(404);exit;}
$path=dirname(__DIR__).'/storage/student-photos/'.$file;
if(!is_file($path)){http_response_code(404);exit;}
$types=['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
header('Content-Type: '.$types[pathinfo($file,PATHINFO_EXTENSION)]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);

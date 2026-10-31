<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/services/live_data.php';
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['DOCUMENT_ROOT']=dirname(__DIR__);
$user=db()->query("SELECT ".tk_cot_phien()." FROM TaiKhoan WHERE vaiTro='HOC_SINH' AND maThiSinh IS NOT NULL LIMIT 1")->fetch();
if(!$user)throw new RuntimeException('Student fixture missing');
$notices=live_student_admission_info();
if(!$notices)throw new RuntimeException('Published notices fixture missing');
$matches=live_student_admission_info($notices[0]['tenTruongTHPT']);
if(!$matches)throw new RuntimeException('School search failed');
foreach($matches as $notice)if($notice['tenTruongTHPT']!==$notices[0]['tenTruongTHPT'])throw new RuntimeException('Search returned unrelated school');
app_session_start();$_SESSION['user']=$user;$_SESSION['last_seen']=time();
ob_start();render_module('admission-info');$html=ob_get_clean();
foreach(['Phương thức tuyển sinh','Hệ đào tạo','Học phí theo thông tin công bố',school_display_name($notices[0]['tenTruongTHPT'])] as $text)if(!str_contains($html,e($text)))throw new RuntimeException('Admission detail missing: '.$text);
foreach(['data-table','data-demo-action','Mã thông tin'] as $text)if(str_contains($html,$text))throw new RuntimeException('Admin table leaked: '.$text);
echo "PASS student admission details, school search and read-only card layout\n";

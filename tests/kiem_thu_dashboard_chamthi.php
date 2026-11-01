<?php
declare(strict_types=1);
require dirname(__DIR__).'/services/live_data.php';

$data=live_grading_dashboard();
if($data===null)throw new RuntimeException('Grading dashboard unavailable');
$pdo=db();
$expectedTotal=(int)$pdo->query('SELECT COUNT(*) FROM BaiThi')->fetchColumn();
$expectedHistory=(int)$pdo->query('SELECT COUNT(*) FROM LichSuChinhSuaDiem')->fetchColumn();
if($data['tongBai']!==$expectedTotal || $data['lichSu']!==$expectedHistory)throw new RuntimeException('Grading dashboard totals mismatch');
if($data['daCham']+$data['chuaCham']!==$data['tongBai'])throw new RuntimeException('Grading progress does not cover every paper');
if(count($data['ganDay'])>6)throw new RuntimeException('Recent grading history is not bounded');
foreach($data['theoMon'] as $row){
    if((int)$row['daCham']+(int)$row['chuaCham']!==(int)$row['tongBai'])throw new RuntimeException('Subject grading progress mismatch');
}
if(array_key_exists('theoTHCS',$data) || array_key_exists('theoTruong',$data))throw new RuntimeException('Admissions data leaked into grading dashboard');
echo "PASS grading dashboard totals, subject progress and role-specific data\n";

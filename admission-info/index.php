<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/live_data.php';

$currentUser = current_user();
$isStudent = has_role($currentUser, ['HOC_SINH']);
$page_title = 'Xem thông tin tuyển sinh lớp 10';
$layout = $isStudent ? 'dashboard' : ($currentUser ? 'dashboard' : 'public');
$active = 'admission-info';
$public_active = 'info';

// Capture search and filter inputs
$admissionSearch = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 120) : '';
$filterRegion = is_string($_GET['khu_vuc'] ?? null) ? trim($_GET['khu_vuc']) : 'all';
$filterSchool = is_string($_GET['truong'] ?? null) ? trim($_GET['truong']) : 'all';
$filterEducation = is_string($_GET['he_dao_tao'] ?? null) ? trim($_GET['he_dao_tao']) : 'all';
$filterMethod = is_string($_GET['phuong_thuc'] ?? null) ? trim($_GET['phuong_thuc']) : 'all';
$viewMode = (isset($_GET['view']) && $_GET['view'] === 'table') ? 'table' : 'grid';

// Fetch admission notices data
$admissionNotices = live_student_admission_info($admissionSearch, [
    'region' => $filterRegion,
    'school' => $filterSchool,
    'education' => $filterEducation,
    'method' => $filterMethod,
]);

require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main" class="<?= $isStudent ? 'student-portal-content' : ($layout === 'dashboard' ? 'dash-content' : 'portal-main') ?>">
  <div class="container">
    <?php require dirname(__DIR__) . '/includes/student_admission_info.php'; ?>
  </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

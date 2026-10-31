<?php
$profileData=$studentDashboard['supplement'];$extra=$profileData['extra'];
$profileNameParts=preg_split('/\s+/u',trim($profile['hoTen']));
$profileGivenName=array_pop($profileNameParts);$profileFamilyName=implode(' ',$profileNameParts);
$defaultAvatarKind = match($extra['gioiTinh'] ?? '') { 'Nam'=>'male', 'Nữ'=>'female', default=>'neutral' };
$defaultAvatarLabel = match($defaultAvatarKind) { 'male'=>'Ảnh đại diện nam', 'female'=>'Ảnh đại diện nữ', default=>'Ảnh đại diện học sinh' };
function student_profile_input(string $name,string $label,string $value,string $type='text',int $max=150): void {
    echo '<div class="student-input"><label for="profile-'.e($name).'">'.e($label).'</label><input id="profile-'.e($name).'" name="'.e($name).'" type="'.e($type).'" value="'.e($value).'" maxlength="'.$max.'"></div>';
}
?>
<?php foreach(flash_take() as $notice): ?><div class="ui-notice ui-notice-<?= ($notice['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status"><?= e($notice['message']) ?></div><?php endforeach; ?>
<?php if(!$profileData['ready']): ?><div class="notice notice-blue" role="alert">Các trường bổ sung chưa sẵn sàng lưu. Vui lòng liên hệ quản trị hệ thống để cập nhật cấu trúc hồ sơ.</div><?php endif; ?>
<form id="student-profile-save" method="post" action="<?= e(url('admin/')) ?>" enctype="multipart/form-data" class="student-profile-form">
<?= csrf_field() ?>
<fieldset<?= !$profileData['ready'] ? ' disabled' : '' ?>>
<div class="student-photo-name">
 <div class="student-photo-box"><?php if(!empty($extra['anhHoSo'])): ?><img src="<?= e(url('auth/student-photo.php')) ?>" alt="Ảnh hồ sơ của bạn" width="100" height="125"><?php else: ?><img class="student-default-avatar" data-student-default-avatar data-avatar-male="<?= e(url('assets/images/student-avatar-male.svg')) ?>" data-avatar-female="<?= e(url('assets/images/student-avatar-female.svg')) ?>" data-avatar-neutral="<?= e(url('assets/images/student-avatar-neutral.svg')) ?>" src="<?= e(url('assets/images/student-avatar-'.$defaultAvatarKind.'.svg')) ?>" alt="<?= e($defaultAvatarLabel) ?>" width="100" height="125"><?php endif; ?><label for="profile-photo">Ảnh hồ sơ</label><input type="file" id="profile-photo" name="anhHoSo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, WebP · tối đa 2 MB</small></div>
 <dl class="student-profile-fields">
  <div><dt>Họ đệm</dt><dd><?= e($profileFamilyName) ?></dd></div>
  <div><dt>Tên</dt><dd><?= e($profileGivenName) ?></dd></div>
  <div><dt>Mã học sinh</dt><dd><?= e($profile['maThiSinh']) ?></dd></div>
  <div><dt>Ngày sinh</dt><dd><?= e(date('d/m/Y',strtotime($profile['ngaySinh']))) ?></dd></div>
  <div><dt>Mã hồ sơ</dt><dd><?= e($profile['maHoSo']) ?></dd></div>
  <div><dt>Số báo danh</dt><dd><?= e($profile['soBaoDanh'] ?: 'Chưa được cấp') ?></dd></div>
 </dl>
</div>
<p class="field-note">Mã học sinh, mã hồ sơ, số báo danh, CCCD và trường THCS do nhà trường hoặc Ban tuyển sinh quản lý; học sinh không thể tự sửa. Nếu thông tin chưa đúng, hãy liên hệ cán bộ phụ trách.</p>
<div class="student-profile-input-grid">
 <div class="student-input"><label for="profile-gender">Giới tính</label><select id="profile-gender" name="gioiTinh"><?php foreach([''=>'Chưa khai báo','Nam'=>'Nam','Nữ'=>'Nữ','Khác'=>'Khác'] as $value=>$text): ?><option value="<?= e($value) ?>"<?= ($extra['gioiTinh'] ?? '')===$value?' selected':'' ?>><?= e($text) ?></option><?php endforeach; ?></select></div>
 <div class="student-input"><label>Số định danh/CCCD</label><div class="student-readonly-field"><?= e((string)($extra['soDinhDanh'] ?? '') ?: 'Chưa cập nhật') ?></div></div>
 <?php student_profile_input('noiSinh','Nơi sinh',(string)($extra['noiSinh'] ?? '')); ?>
 <?php student_profile_input('danToc','Dân tộc',(string)($extra['danToc'] ?? ''),'text',50); ?>
 <?php student_profile_input('quocTich','Quốc tịch',(string)($extra['quocTich'] ?? ''),'text',50); ?>
 <?php student_profile_input('tonGiao','Tôn giáo',(string)($extra['tonGiao'] ?? ''),'text',50); ?>
 <?php student_profile_input('dienThoai','Điện thoại',(string)($extra['dienThoai'] ?? ''),'tel',20); ?>
 <?php student_profile_input('email','Email',(string)($extra['email'] ?? ''),'email',150); ?>
 <div class="student-input"><label>Trường đang học · THCS</label><div class="student-readonly-field"><?= e(school_display_name($profile['tenTruongTHCS'])) ?></div></div>
 <?php student_profile_input('lop','Lớp',(string)($extra['lop'] ?? ''),'text',20); ?>
</div>
<?php student_profile_input('diaChiThuongTru','Địa chỉ thường trú',(string)($extra['diaChiThuongTru'] ?? ''),'text',500); ?>
<?php student_profile_input('choOHienNay','Chỗ ở hiện nay',(string)($extra['choOHienNay'] ?? ''),'text',500); ?>
<details class="student-extra-section"><summary>Thông tin điểm cộng theo đối tượng</summary><?php student_profile_input('doiTuongUuTien','Đối tượng ưu tiên theo hồ sơ',(string)($extra['doiTuongUuTien'] ?? ''),'text',250); ?><p class="field-note">Thông tin khai báo cần được cán bộ đối chiếu; không tự cộng vào điểm xét tuyển.</p></details>
<details class="student-extra-section" open><summary>Thông tin gia đình</summary>
<?php foreach(['Me'=>'Mẹ','Cha'=>'Cha','GiamHo'=>'Người giám hộ'] as $relation=>$label): $family=$profileData['family'][$relation] ?? []; ?>
<div class="student-family-row"><strong><?= e($label) ?></strong><div class="student-profile-input-grid">
 <?php student_profile_input('family['.$relation.'][hoTen]','Họ tên',(string)($family['hoTen'] ?? ''),'text',100); ?>
 <?php student_profile_input('family['.$relation.'][namSinh]','Năm sinh',(string)($family['namSinh'] ?? ''),'text',4); ?>
 <?php student_profile_input('family['.$relation.'][dienThoai]','Điện thoại',(string)($family['dienThoai'] ?? ''),'tel',20); ?>
</div></div><?php endforeach; ?></details>
<details class="student-extra-section" open><summary>Thông tin năm học trước</summary>
<?php foreach(range(6,9) as $grade): $year=$profileData['schoolYears'][$grade] ?? []; ?>
<div class="student-year-row"><h3>Lớp <?= $grade ?></h3><div class="student-profile-input-grid">
 <?php student_profile_input('years['.$grade.'][diemTrungBinh]','Điểm trung bình',(string)($year['diemTrungBinh'] ?? ''),'text',5); ?>
 <?php foreach(['hocLuc'=>'Học tập / học lực','hanhKiem'=>'Rèn luyện / hạnh kiểm'] as $key=>$label): ?><div class="student-input"><label for="profile-year-<?= $grade ?>-<?= e($key) ?>"><?= e($label) ?></label><select id="profile-year-<?= $grade ?>-<?= e($key) ?>" name="years[<?= $grade ?>][<?= e($key) ?>]"><?php foreach($key==='hocLuc'?['','Tốt','Khá','Đạt','Chưa đạt','Giỏi','Trung bình','Yếu','Kém']:['','Tốt','Khá','Đạt','Chưa đạt','Trung bình','Yếu'] as $option): ?><option value="<?= e($option) ?>"<?= ($year[$key] ?? '')===$option?' selected':'' ?>><?= e($option ?: 'Chưa khai báo') ?></option><?php endforeach; ?></select></div><?php endforeach; ?>
</div></div><?php endforeach; ?></details>
<button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
</fieldset></form>

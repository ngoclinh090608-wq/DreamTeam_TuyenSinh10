<?php $quickLinks = dashboard_quick_links(current_user()); ?>
<div class="quick-links">
 <?php foreach ($quickLinks as [$moduleKey, $path, $label, $description, $icon]): ?>
 <a href="<?= e(url($path)) ?>"><span class="quick-icon"><?= e($icon) ?></span><span><?= e($label) ?><small><?= e($description) ?></small></span>→</a>
 <?php endforeach; ?>
 <?php if (!$quickLinks): ?><p>Chưa có chức năng được cấp cho tài khoản này. Vui lòng liên hệ quản trị hệ thống.</p><?php endif; ?>
</div>

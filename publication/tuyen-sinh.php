<?php
declare(strict_types=1);
/** UC22 – Công bố kết quả tuyển sinh (phần của Nguyễn Thị Cẩm Vân). Giữ nguyên giao diện mẫu cũ của publication/. */
require_once dirname(__DIR__) . '/includes/auth.php';
require_role(['HD_TUYEN_SINH']);   // UC22 – Hội đồng tuyển sinh
render_module('publication');

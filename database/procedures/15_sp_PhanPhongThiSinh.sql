-- 5.15. sp_PhanPhongThiSinh (Nguyễn Thị Thúy Vy) – UC07.
-- Phân thí sinh vào phòng thi cụ thể, kiểm tra điều kiện dự thi và sức chứa phòng.
DROP PROCEDURE IF EXISTS sp_PhanPhongThiSinh;
DELIMITER $$
CREATE PROCEDURE sp_PhanPhongThiSinh(
  IN  p_maPhanPhong VARCHAR(20),
  IN  p_maThiSinh VARCHAR(20),
  IN  p_maPhong VARCHAR(20),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_sucChua INT DEFAULT 0;
  DECLARE v_daPhan INT DEFAULT 0;
  DECLARE v_dieuKien INT DEFAULT 0;
  DECLARE v_trangThaiDk VARCHAR(50);
  DECLARE v_daCoPhong INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu kết quả phân phòng.';
  END;

  SET p_thongBao = '';

  IF p_maPhanPhong IS NULL OR TRIM(p_maPhanPhong) = '' OR
     p_maThiSinh IS NULL OR TRIM(p_maThiSinh) = '' OR
     p_maPhong IS NULL OR TRIM(p_maPhong) = '' THEN
    SET p_thongBao = 'Thông tin phân phòng không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra phòng thi và sức chứa
  SELECT sucChua INTO v_sucChua FROM PhongThi WHERE maPhong = p_maPhong;
  IF v_sucChua IS NULL OR v_sucChua <= 0 THEN
    SET p_thongBao = 'Phòng thi không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  -- Kiểm tra số thí sinh hiện tại trong phòng
  SELECT COUNT(*) INTO v_daPhan FROM PhanPhongThi WHERE maPhong = p_maPhong AND trangThai = 'Đã phân';
  IF v_daPhan >= v_sucChua THEN
    SET p_thongBao = 'Sức chứa phòng thi không đủ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra thí sinh
  SELECT dieuKienDuThi, trangThaiDangKyDuThi INTO v_dieuKien, v_trangThaiDk
  FROM ThiSinh WHERE maThiSinh = p_maThiSinh;
  IF v_dieuKien IS NULL THEN
    SET p_thongBao = 'Thí sinh không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  IF v_dieuKien <> 1 OR v_trangThaiDk <> 'Đã duyệt' THEN
    SET p_thongBao = 'Thí sinh chưa đủ điều kiện dự thi.';
    LEAVE proc;
  END IF;

  -- Kiểm tra thí sinh đã được phân phòng chưa
  SELECT COUNT(*) INTO v_daCoPhong
  FROM PhanPhongThi
  WHERE maThiSinh = p_maThiSinh AND trangThai = 'Đã phân';
  IF v_daCoPhong > 0 THEN
    SET p_thongBao = 'Thí sinh đã được phân phòng thi trước đó.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  INSERT INTO PhanPhongThi (maPhanPhong, maThiSinh, maPhong, ngayPhan, trangThai)
  VALUES (p_maPhanPhong, p_maThiSinh, p_maPhong, NOW(), 'Đã phân');
  COMMIT;

  SET p_thongBao = 'Phân phòng thi thành công.';
END$$
DELIMITER ;


-- 5.14. sp_QuanLyPhongThi (Nguyễn Thị Thúy Vy) – UC06.
-- Quản lý phòng thi (Thêm/Sửa/Xóa/Xem). Chặn trùng mã phòng hoặc trùng tên phòng tại cùng điểm thi.
DROP PROCEDURE IF EXISTS sp_QuanLyPhongThi;
DELIMITER $$
CREATE PROCEDURE sp_QuanLyPhongThi(
  IN  p_hanhDong VARCHAR(20),
  IN  p_maPhong VARCHAR(20),
  IN  p_tenPhong VARCHAR(100),
  IN  p_sucChua INT,
  IN  p_maDiemThi VARCHAR(20),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_tonTai INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu thông tin phòng thi.';
  END;

  SET p_thongBao = '';

  IF p_hanhDong = 'Them' THEN
    IF p_maPhong IS NULL OR TRIM(p_maPhong) = '' OR
       p_tenPhong IS NULL OR TRIM(p_tenPhong) = '' OR
       p_sucChua IS NULL OR p_sucChua <= 0 OR
       p_maDiemThi IS NULL OR TRIM(p_maDiemThi) = '' THEN
      SET p_thongBao = 'Thông tin phòng thi không hợp lệ.';
      LEAVE proc;
    END IF;

    -- Kiểm tra điểm thi tồn tại
    SELECT COUNT(*) INTO v_tonTai FROM DiemThi WHERE maDiemThi = p_maDiemThi;
    IF v_tonTai = 0 THEN
      SET p_thongBao = 'Điểm thi không tồn tại trong hệ thống.';
      LEAVE proc;
    END IF;

    -- Kiểm tra trùng mã phòng hoặc trùng tên phòng tại cùng điểm thi
    SELECT COUNT(*) INTO v_tonTai
    FROM PhongThi
    WHERE maPhong = p_maPhong OR (maDiemThi = p_maDiemThi AND tenPhong = p_tenPhong);
    IF v_tonTai > 0 THEN
      SET p_thongBao = 'Thông tin phòng thi bị trùng.';
      LEAVE proc;
    END IF;

    START TRANSACTION;
    INSERT INTO PhongThi (maPhong, tenPhong, sucChua, maDiemThi)
    VALUES (p_maPhong, p_tenPhong, p_sucChua, p_maDiemThi);
    COMMIT;
    SET p_thongBao = 'Tạo thông tin phòng thi thành công.';

  ELSEIF p_hanhDong = 'Sua' THEN
    IF p_maPhong IS NULL OR TRIM(p_maPhong) = '' OR
       p_tenPhong IS NULL OR TRIM(p_tenPhong) = '' OR
       p_sucChua IS NULL OR p_sucChua <= 0 THEN
      SET p_thongBao = 'Thông tin phòng thi không hợp lệ.';
      LEAVE proc;
    END IF;

    -- Kiểm tra phòng tồn tại
    SELECT COUNT(*) INTO v_tonTai FROM PhongThi WHERE maPhong = p_maPhong;
    IF v_tonTai = 0 THEN
      SET p_thongBao = 'Phòng thi không tồn tại.';
      LEAVE proc;
    END IF;

    -- Kiểm tra trùng tên phòng khác tại cùng điểm thi
    SELECT COUNT(*) INTO v_tonTai
    FROM PhongThi
    WHERE maDiemThi = p_maDiemThi AND tenPhong = p_tenPhong AND maPhong <> p_maPhong;
    IF v_tonTai > 0 THEN
      SET p_thongBao = 'Thông tin phòng thi bị trùng.';
      LEAVE proc;
    END IF;

    START TRANSACTION;
    UPDATE PhongThi
    SET tenPhong = p_tenPhong, sucChua = p_sucChua
    WHERE maPhong = p_maPhong;
    COMMIT;
    SET p_thongBao = 'Cập nhật thông tin phòng thi thành công.';

  ELSEIF p_hanhDong = 'Xoa' THEN
    -- Không cho xóa nếu đã có thí sinh phân vào phòng
    SELECT COUNT(*) INTO v_tonTai FROM PhanPhongThi WHERE maPhong = p_maPhong;
    IF v_tonTai > 0 THEN
      SET p_thongBao = 'Không thể xóa phòng thi đã có thí sinh được phân phòng.';
      LEAVE proc;
    END IF;

    START TRANSACTION;
    DELETE FROM PhongThi WHERE maPhong = p_maPhong;
    COMMIT;
    SET p_thongBao = 'Xóa phòng thi thành công.';

  ELSE
    SET p_thongBao = 'Hành động không hợp lệ.';
  END IF;
END$$
DELIMITER ;


-- 5.25. sp_NhapKetQuaChamThi (Nguyễn Thị Thúy Vy) – UC05.
-- Nhập điểm lần đầu cho bài thi theo mã phách/mã bài thi. Cập nhật trangThaiCham = 'Đã chấm'.
DROP PROCEDURE IF EXISTS sp_NhapKetQuaChamThi;
DELIMITER $$
CREATE PROCEDURE sp_NhapKetQuaChamThi(
  IN  p_maKetQuaChamThi VARCHAR(20),
  IN  p_maBaiThi VARCHAR(20),
  IN  p_diemThi DECIMAL(5,2),
  IN  p_nguoiNhap VARCHAR(100),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_thangDiem DECIMAL(5,2) DEFAULT NULL;
  DECLARE v_trangThaiCham VARCHAR(50) DEFAULT NULL;
  DECLARE v_daCoDiem INT DEFAULT 0;
  DECLARE v_trangThaiCongBo VARCHAR(50) DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu điểm thi.';
  END;

  SET p_thongBao = '';

  IF p_maBaiThi IS NULL OR TRIM(p_maBaiThi) = '' OR
     p_nguoiNhap IS NULL OR TRIM(p_nguoiNhap) = '' THEN
    SET p_thongBao = 'Thông tin bài thi hoặc người nhập không hợp lệ.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  -- Khóa dòng bài thi để kiểm tra
  SELECT b.trangThaiCham, m.thangDiem INTO v_trangThaiCham, v_thangDiem
  FROM BaiThi b
  JOIN MonThi m ON m.maMonThi = b.maMonThi
  WHERE b.maBaiThi = p_maBaiThi
  FOR UPDATE;

  IF v_thangDiem IS NULL THEN
    ROLLBACK;
    SET p_thongBao = 'Không tìm thấy bài thi cần nhập điểm.';
    LEAVE proc;
  END IF;

  -- Kiểm tra điểm hợp lệ: 0 <= điểm <= thangDiem và chia hết cho 0.25
  IF p_diemThi IS NULL OR p_diemThi < 0 OR p_diemThi > v_thangDiem OR MOD(p_diemThi * 100, 25) <> 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Điểm thi không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra nếu bài thi đã có kết quả
  SELECT COUNT(*), MAX(trangThaiCongBo) INTO v_daCoDiem, v_trangThaiCongBo
  FROM KetQuaChamThi
  WHERE maBaiThi = p_maBaiThi;

  IF v_daCoDiem > 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Điểm bài thi đã được xác nhận chính thức.';
    LEAVE proc;
  END IF;

  -- Thêm kết quả chấm thi
  INSERT INTO KetQuaChamThi (maKetQuaChamThi, diemThi, nguoiNhap, thoiDiemNhap, trangThaiCongBo, maBaiThi)
  VALUES (p_maKetQuaChamThi, p_diemThi, p_nguoiNhap, NOW(), 'Chưa công bố', p_maBaiThi);

  -- Cập nhật trạng thái bài thi thành 'Đã chấm'
  UPDATE BaiThi
  SET trangThaiCham = 'Đã chấm'
  WHERE maBaiThi = p_maBaiThi;

  COMMIT;
  SET p_thongBao = 'Nhập điểm thi thành công.';
END$$
DELIMITER ;


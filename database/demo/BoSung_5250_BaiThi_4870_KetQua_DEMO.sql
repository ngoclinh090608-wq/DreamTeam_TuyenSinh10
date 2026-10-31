-- BO SUNG DU LIEU THI THU CHO 1.750 HOC SINH / 5 TRUONG THCS (HOAN TOAN MO PHONG)
-- DU AN: DreamTeam_TuyenSinh10, schema repo nhanh linh (31/10/2026)
-- CHI CHAY TREN dreamteam_tuyensinh10_demo, KHONG CHAY TREN DB CHINH
-- Dieu kien: da import file 01_5_truong_thcs_1750_thi_sinh.sql; da co seed goc KT2026, DOT2026, MonThi TOAN/VAN/NN
-- KHONG xoa/sua 54 BaiThi, 51 KetQuaChamThi cu va LichSuChinhSuaDiem cu.
-- Tao ky thi RIENG KTDEMO26 de demo UC15: TOAN du 1750/1750, VAN 1600/1750, NN 1520/1750.
-- De tao duoc 'LichSuChinhSuaDiem' moi, can sua diem thong qua UC13 (khong tao lich su gia).
-- Co the chay lai: chi bo sung ban ghi thieu; khong ghi de diem/cong bo da thay doi.
SET NAMES utf8mb4;
USE `dreamteam_tuyensinh10_demo`;
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_demo_1750_import_baithi $$
CREATE PROCEDURE sp_demo_1750_import_baithi()
BEGIN
  DECLARE v_n INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  START TRANSACTION;
  -- KIEM TRA DU LIEU GOC: bao ve khoa ngoai va tranh tao bai thi cho hoc sinh khac.
  SELECT COUNT(*) INTO v_n FROM ThiSinh WHERE maThiSinh REGEXP '^TSD[0-9]{6}$' AND CAST(SUBSTRING(maThiSinh, 4) AS UNSIGNED) BETWEEN 1 AND 1750;
  IF v_n <> 1750 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thieu 1.750 thi sinh TSD000001..TSD001750; import file 01 truoc.';
  END IF;
  SELECT COUNT(*) INTO v_n FROM ThiSinh WHERE maTruongTHCS = 'THCS_DEMO1' AND maThiSinh REGEXP '^TSD[0-9]{6}$';
  IF v_n <> 320 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sai so hoc sinh THCS_DEMO1; yeu cau 320.'; END IF;
  SELECT COUNT(*) INTO v_n FROM ThiSinh WHERE maTruongTHCS = 'THCS_DEMO2' AND maThiSinh REGEXP '^TSD[0-9]{6}$';
  IF v_n <> 335 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sai so hoc sinh THCS_DEMO2; yeu cau 335.'; END IF;
  SELECT COUNT(*) INTO v_n FROM ThiSinh WHERE maTruongTHCS = 'THCS_DEMO3' AND maThiSinh REGEXP '^TSD[0-9]{6}$';
  IF v_n <> 350 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sai so hoc sinh THCS_DEMO3; yeu cau 350.'; END IF;
  SELECT COUNT(*) INTO v_n FROM ThiSinh WHERE maTruongTHCS = 'THCS_DEMO4' AND maThiSinh REGEXP '^TSD[0-9]{6}$';
  IF v_n <> 365 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sai so hoc sinh THCS_DEMO4; yeu cau 365.'; END IF;
  SELECT COUNT(*) INTO v_n FROM ThiSinh WHERE maTruongTHCS = 'THCS_DEMO5' AND maThiSinh REGEXP '^TSD[0-9]{6}$';
  IF v_n <> 380 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sai so hoc sinh THCS_DEMO5; yeu cau 380.'; END IF;
  SELECT COUNT(*) INTO v_n FROM DotTuyenSinh WHERE maDot = 'DOT2026';
  IF v_n <> 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Khong co DOT2026. Can seed goc.'; END IF;
  SELECT COUNT(*) INTO v_n FROM MonThi WHERE maMonThi IN ('TOAN', 'VAN', 'NN') AND thangDiem >= 10;
  IF v_n <> 3 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Can 3 mon thi TOAN, VAN, NN thang 10.'; END IF;
  SELECT COUNT(*) INTO v_n FROM BaiThi WHERE maBaiThi LIKE 'BTD%' AND maKyThi <> 'KTDEMO26';
  IF v_n > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Trung tien to BTD cua BaiThi o ky thi khac!'; END IF;
  -- BOI CANH THI RIENG, KHONG TRON 54 BAI THI CU.
  INSERT IGNORE INTO KyThi (maKyThi,tenKyThi,thoiGianKetThuc,maDot) VALUES
    ('KTDEMO26','Kỳ thi DEMO tuyển sinh lớp 10 - 1.750 học sinh','2026-06-12 12:00:00','DOT2026');
  INSERT IGNORE INTO DiemThi (maDiemThi,tenDiemThi,maTruongTHCS,maTruongTHPT,maKyThi) VALUES
    ('DTD1','Điểm thi demo THCS Sao Mai','THCS_DEMO1',NULL,'KTDEMO26'),
    ('DTD2','Điểm thi demo THCS Bình Minh','THCS_DEMO2',NULL,'KTDEMO26'),
    ('DTD3','Điểm thi demo THCS Hòa An','THCS_DEMO3',NULL,'KTDEMO26'),
    ('DTD4','Điểm thi demo THCS Ánh Dương','THCS_DEMO4',NULL,'KTDEMO26'),
    ('DTD5','Điểm thi demo THCS Tương Lai','THCS_DEMO5',NULL,'KTDEMO26');
  -- 61 phong thi, suc chua 30 hoc sinh / phong. KHONG sua phong thi cu.
  INSERT IGNORE INTO PhongThi (maPhong,tenPhong,sucChua,maDiemThi) VALUES
    ('RD1P01','Phòng demo 1-01',30,'DTD1'),
    ('RD1P02','Phòng demo 1-02',30,'DTD1'),
    ('RD1P03','Phòng demo 1-03',30,'DTD1'),
    ('RD1P04','Phòng demo 1-04',30,'DTD1'),
    ('RD1P05','Phòng demo 1-05',30,'DTD1'),
    ('RD1P06','Phòng demo 1-06',30,'DTD1'),
    ('RD1P07','Phòng demo 1-07',30,'DTD1'),
    ('RD1P08','Phòng demo 1-08',30,'DTD1'),
    ('RD1P09','Phòng demo 1-09',30,'DTD1'),
    ('RD1P10','Phòng demo 1-10',30,'DTD1'),
    ('RD1P11','Phòng demo 1-11',30,'DTD1'),
    ('RD2P01','Phòng demo 2-01',30,'DTD2'),
    ('RD2P02','Phòng demo 2-02',30,'DTD2'),
    ('RD2P03','Phòng demo 2-03',30,'DTD2'),
    ('RD2P04','Phòng demo 2-04',30,'DTD2'),
    ('RD2P05','Phòng demo 2-05',30,'DTD2'),
    ('RD2P06','Phòng demo 2-06',30,'DTD2'),
    ('RD2P07','Phòng demo 2-07',30,'DTD2'),
    ('RD2P08','Phòng demo 2-08',30,'DTD2'),
    ('RD2P09','Phòng demo 2-09',30,'DTD2'),
    ('RD2P10','Phòng demo 2-10',30,'DTD2'),
    ('RD2P11','Phòng demo 2-11',30,'DTD2'),
    ('RD2P12','Phòng demo 2-12',30,'DTD2'),
    ('RD3P01','Phòng demo 3-01',30,'DTD3'),
    ('RD3P02','Phòng demo 3-02',30,'DTD3'),
    ('RD3P03','Phòng demo 3-03',30,'DTD3'),
    ('RD3P04','Phòng demo 3-04',30,'DTD3'),
    ('RD3P05','Phòng demo 3-05',30,'DTD3'),
    ('RD3P06','Phòng demo 3-06',30,'DTD3'),
    ('RD3P07','Phòng demo 3-07',30,'DTD3'),
    ('RD3P08','Phòng demo 3-08',30,'DTD3'),
    ('RD3P09','Phòng demo 3-09',30,'DTD3'),
    ('RD3P10','Phòng demo 3-10',30,'DTD3'),
    ('RD3P11','Phòng demo 3-11',30,'DTD3'),
    ('RD3P12','Phòng demo 3-12',30,'DTD3'),
    ('RD4P01','Phòng demo 4-01',30,'DTD4'),
    ('RD4P02','Phòng demo 4-02',30,'DTD4'),
    ('RD4P03','Phòng demo 4-03',30,'DTD4'),
    ('RD4P04','Phòng demo 4-04',30,'DTD4'),
    ('RD4P05','Phòng demo 4-05',30,'DTD4'),
    ('RD4P06','Phòng demo 4-06',30,'DTD4'),
    ('RD4P07','Phòng demo 4-07',30,'DTD4'),
    ('RD4P08','Phòng demo 4-08',30,'DTD4'),
    ('RD4P09','Phòng demo 4-09',30,'DTD4'),
    ('RD4P10','Phòng demo 4-10',30,'DTD4'),
    ('RD4P11','Phòng demo 4-11',30,'DTD4'),
    ('RD4P12','Phòng demo 4-12',30,'DTD4'),
    ('RD4P13','Phòng demo 4-13',30,'DTD4'),
    ('RD5P01','Phòng demo 5-01',30,'DTD5'),
    ('RD5P02','Phòng demo 5-02',30,'DTD5'),
    ('RD5P03','Phòng demo 5-03',30,'DTD5'),
    ('RD5P04','Phòng demo 5-04',30,'DTD5'),
    ('RD5P05','Phòng demo 5-05',30,'DTD5'),
    ('RD5P06','Phòng demo 5-06',30,'DTD5'),
    ('RD5P07','Phòng demo 5-07',30,'DTD5'),
    ('RD5P08','Phòng demo 5-08',30,'DTD5'),
    ('RD5P09','Phòng demo 5-09',30,'DTD5'),
    ('RD5P10','Phòng demo 5-10',30,'DTD5'),
    ('RD5P11','Phòng demo 5-11',30,'DTD5'),
    ('RD5P12','Phòng demo 5-12',30,'DTD5'),
    ('RD5P13','Phòng demo 5-13',30,'DTD5');
  -- Phan phong thi cho 1750 hoc sinh (ma PPD000001..PPD001750).
  INSERT IGNORE INTO PhanPhongThi (maPhanPhong,maThiSinh,maPhong,ngayPhan,trangThai)
  SELECT CONCAT('PPD',SUBSTRING(t.maThiSinh,4)), t.maThiSinh,
    CONCAT('RD',SUBSTRING(t.maTruongTHCS,10),'P',LPAD(FLOOR((CAST(SUBSTRING(t.maThiSinh,4) AS UNSIGNED) - c.st - 1)/30)+1,2,'0')),
    '2026-06-01 08:00:00','Đã phân'
  FROM ThiSinh t JOIN (
    SELECT 'THCS_DEMO1' AS sch, 0 AS st UNION ALL SELECT 'THCS_DEMO2',320
    UNION ALL SELECT 'THCS_DEMO3',655 UNION ALL SELECT 'THCS_DEMO4',1005 UNION ALL SELECT 'THCS_DEMO5',1370
  ) c ON c.sch = t.maTruongTHCS
  WHERE t.maThiSinh REGEXP '^TSD[0-9]{6}$' AND CAST(SUBSTRING(t.maThiSinh,4) AS UNSIGNED) BETWEEN 1 AND 1750;
  -- 3 lo bai thi/1 phong, gom 183 lo; ma LD1P01TOAN, LD1P01VAN, LD1P01NN...
  INSERT IGNORE INTO LoBaiThi (maLoBaiThi,thongTinLoBaiThi,maMonThi,maPhong) VALUES
    ('LD1P01TOAN','Lô bài thi DEMO TOAN - phòng RD1P01','TOAN','RD1P01'),
    ('LD1P01VAN','Lô bài thi DEMO VAN - phòng RD1P01','VAN','RD1P01'),
    ('LD1P01NN','Lô bài thi DEMO NN - phòng RD1P01','NN','RD1P01'),
    ('LD1P02TOAN','Lô bài thi DEMO TOAN - phòng RD1P02','TOAN','RD1P02'),
    ('LD1P02VAN','Lô bài thi DEMO VAN - phòng RD1P02','VAN','RD1P02'),
    ('LD1P02NN','Lô bài thi DEMO NN - phòng RD1P02','NN','RD1P02'),
    ('LD1P03TOAN','Lô bài thi DEMO TOAN - phòng RD1P03','TOAN','RD1P03'),
    ('LD1P03VAN','Lô bài thi DEMO VAN - phòng RD1P03','VAN','RD1P03'),
    ('LD1P03NN','Lô bài thi DEMO NN - phòng RD1P03','NN','RD1P03'),
    ('LD1P04TOAN','Lô bài thi DEMO TOAN - phòng RD1P04','TOAN','RD1P04'),
    ('LD1P04VAN','Lô bài thi DEMO VAN - phòng RD1P04','VAN','RD1P04'),
    ('LD1P04NN','Lô bài thi DEMO NN - phòng RD1P04','NN','RD1P04'),
    ('LD1P05TOAN','Lô bài thi DEMO TOAN - phòng RD1P05','TOAN','RD1P05'),
    ('LD1P05VAN','Lô bài thi DEMO VAN - phòng RD1P05','VAN','RD1P05'),
    ('LD1P05NN','Lô bài thi DEMO NN - phòng RD1P05','NN','RD1P05'),
    ('LD1P06TOAN','Lô bài thi DEMO TOAN - phòng RD1P06','TOAN','RD1P06'),
    ('LD1P06VAN','Lô bài thi DEMO VAN - phòng RD1P06','VAN','RD1P06'),
    ('LD1P06NN','Lô bài thi DEMO NN - phòng RD1P06','NN','RD1P06'),
    ('LD1P07TOAN','Lô bài thi DEMO TOAN - phòng RD1P07','TOAN','RD1P07'),
    ('LD1P07VAN','Lô bài thi DEMO VAN - phòng RD1P07','VAN','RD1P07'),
    ('LD1P07NN','Lô bài thi DEMO NN - phòng RD1P07','NN','RD1P07'),
    ('LD1P08TOAN','Lô bài thi DEMO TOAN - phòng RD1P08','TOAN','RD1P08'),
    ('LD1P08VAN','Lô bài thi DEMO VAN - phòng RD1P08','VAN','RD1P08'),
    ('LD1P08NN','Lô bài thi DEMO NN - phòng RD1P08','NN','RD1P08'),
    ('LD1P09TOAN','Lô bài thi DEMO TOAN - phòng RD1P09','TOAN','RD1P09'),
    ('LD1P09VAN','Lô bài thi DEMO VAN - phòng RD1P09','VAN','RD1P09'),
    ('LD1P09NN','Lô bài thi DEMO NN - phòng RD1P09','NN','RD1P09'),
    ('LD1P10TOAN','Lô bài thi DEMO TOAN - phòng RD1P10','TOAN','RD1P10'),
    ('LD1P10VAN','Lô bài thi DEMO VAN - phòng RD1P10','VAN','RD1P10'),
    ('LD1P10NN','Lô bài thi DEMO NN - phòng RD1P10','NN','RD1P10'),
    ('LD1P11TOAN','Lô bài thi DEMO TOAN - phòng RD1P11','TOAN','RD1P11'),
    ('LD1P11VAN','Lô bài thi DEMO VAN - phòng RD1P11','VAN','RD1P11'),
    ('LD1P11NN','Lô bài thi DEMO NN - phòng RD1P11','NN','RD1P11'),
    ('LD2P01TOAN','Lô bài thi DEMO TOAN - phòng RD2P01','TOAN','RD2P01'),
    ('LD2P01VAN','Lô bài thi DEMO VAN - phòng RD2P01','VAN','RD2P01'),
    ('LD2P01NN','Lô bài thi DEMO NN - phòng RD2P01','NN','RD2P01'),
    ('LD2P02TOAN','Lô bài thi DEMO TOAN - phòng RD2P02','TOAN','RD2P02'),
    ('LD2P02VAN','Lô bài thi DEMO VAN - phòng RD2P02','VAN','RD2P02'),
    ('LD2P02NN','Lô bài thi DEMO NN - phòng RD2P02','NN','RD2P02'),
    ('LD2P03TOAN','Lô bài thi DEMO TOAN - phòng RD2P03','TOAN','RD2P03'),
    ('LD2P03VAN','Lô bài thi DEMO VAN - phòng RD2P03','VAN','RD2P03'),
    ('LD2P03NN','Lô bài thi DEMO NN - phòng RD2P03','NN','RD2P03'),
    ('LD2P04TOAN','Lô bài thi DEMO TOAN - phòng RD2P04','TOAN','RD2P04'),
    ('LD2P04VAN','Lô bài thi DEMO VAN - phòng RD2P04','VAN','RD2P04'),
    ('LD2P04NN','Lô bài thi DEMO NN - phòng RD2P04','NN','RD2P04'),
    ('LD2P05TOAN','Lô bài thi DEMO TOAN - phòng RD2P05','TOAN','RD2P05'),
    ('LD2P05VAN','Lô bài thi DEMO VAN - phòng RD2P05','VAN','RD2P05'),
    ('LD2P05NN','Lô bài thi DEMO NN - phòng RD2P05','NN','RD2P05'),
    ('LD2P06TOAN','Lô bài thi DEMO TOAN - phòng RD2P06','TOAN','RD2P06'),
    ('LD2P06VAN','Lô bài thi DEMO VAN - phòng RD2P06','VAN','RD2P06'),
    ('LD2P06NN','Lô bài thi DEMO NN - phòng RD2P06','NN','RD2P06'),
    ('LD2P07TOAN','Lô bài thi DEMO TOAN - phòng RD2P07','TOAN','RD2P07'),
    ('LD2P07VAN','Lô bài thi DEMO VAN - phòng RD2P07','VAN','RD2P07'),
    ('LD2P07NN','Lô bài thi DEMO NN - phòng RD2P07','NN','RD2P07'),
    ('LD2P08TOAN','Lô bài thi DEMO TOAN - phòng RD2P08','TOAN','RD2P08'),
    ('LD2P08VAN','Lô bài thi DEMO VAN - phòng RD2P08','VAN','RD2P08'),
    ('LD2P08NN','Lô bài thi DEMO NN - phòng RD2P08','NN','RD2P08'),
    ('LD2P09TOAN','Lô bài thi DEMO TOAN - phòng RD2P09','TOAN','RD2P09'),
    ('LD2P09VAN','Lô bài thi DEMO VAN - phòng RD2P09','VAN','RD2P09'),
    ('LD2P09NN','Lô bài thi DEMO NN - phòng RD2P09','NN','RD2P09'),
    ('LD2P10TOAN','Lô bài thi DEMO TOAN - phòng RD2P10','TOAN','RD2P10'),
    ('LD2P10VAN','Lô bài thi DEMO VAN - phòng RD2P10','VAN','RD2P10'),
    ('LD2P10NN','Lô bài thi DEMO NN - phòng RD2P10','NN','RD2P10'),
    ('LD2P11TOAN','Lô bài thi DEMO TOAN - phòng RD2P11','TOAN','RD2P11'),
    ('LD2P11VAN','Lô bài thi DEMO VAN - phòng RD2P11','VAN','RD2P11'),
    ('LD2P11NN','Lô bài thi DEMO NN - phòng RD2P11','NN','RD2P11'),
    ('LD2P12TOAN','Lô bài thi DEMO TOAN - phòng RD2P12','TOAN','RD2P12'),
    ('LD2P12VAN','Lô bài thi DEMO VAN - phòng RD2P12','VAN','RD2P12'),
    ('LD2P12NN','Lô bài thi DEMO NN - phòng RD2P12','NN','RD2P12'),
    ('LD3P01TOAN','Lô bài thi DEMO TOAN - phòng RD3P01','TOAN','RD3P01'),
    ('LD3P01VAN','Lô bài thi DEMO VAN - phòng RD3P01','VAN','RD3P01'),
    ('LD3P01NN','Lô bài thi DEMO NN - phòng RD3P01','NN','RD3P01'),
    ('LD3P02TOAN','Lô bài thi DEMO TOAN - phòng RD3P02','TOAN','RD3P02'),
    ('LD3P02VAN','Lô bài thi DEMO VAN - phòng RD3P02','VAN','RD3P02'),
    ('LD3P02NN','Lô bài thi DEMO NN - phòng RD3P02','NN','RD3P02'),
    ('LD3P03TOAN','Lô bài thi DEMO TOAN - phòng RD3P03','TOAN','RD3P03'),
    ('LD3P03VAN','Lô bài thi DEMO VAN - phòng RD3P03','VAN','RD3P03'),
    ('LD3P03NN','Lô bài thi DEMO NN - phòng RD3P03','NN','RD3P03'),
    ('LD3P04TOAN','Lô bài thi DEMO TOAN - phòng RD3P04','TOAN','RD3P04'),
    ('LD3P04VAN','Lô bài thi DEMO VAN - phòng RD3P04','VAN','RD3P04'),
    ('LD3P04NN','Lô bài thi DEMO NN - phòng RD3P04','NN','RD3P04'),
    ('LD3P05TOAN','Lô bài thi DEMO TOAN - phòng RD3P05','TOAN','RD3P05'),
    ('LD3P05VAN','Lô bài thi DEMO VAN - phòng RD3P05','VAN','RD3P05'),
    ('LD3P05NN','Lô bài thi DEMO NN - phòng RD3P05','NN','RD3P05'),
    ('LD3P06TOAN','Lô bài thi DEMO TOAN - phòng RD3P06','TOAN','RD3P06'),
    ('LD3P06VAN','Lô bài thi DEMO VAN - phòng RD3P06','VAN','RD3P06'),
    ('LD3P06NN','Lô bài thi DEMO NN - phòng RD3P06','NN','RD3P06'),
    ('LD3P07TOAN','Lô bài thi DEMO TOAN - phòng RD3P07','TOAN','RD3P07'),
    ('LD3P07VAN','Lô bài thi DEMO VAN - phòng RD3P07','VAN','RD3P07'),
    ('LD3P07NN','Lô bài thi DEMO NN - phòng RD3P07','NN','RD3P07'),
    ('LD3P08TOAN','Lô bài thi DEMO TOAN - phòng RD3P08','TOAN','RD3P08'),
    ('LD3P08VAN','Lô bài thi DEMO VAN - phòng RD3P08','VAN','RD3P08'),
    ('LD3P08NN','Lô bài thi DEMO NN - phòng RD3P08','NN','RD3P08'),
    ('LD3P09TOAN','Lô bài thi DEMO TOAN - phòng RD3P09','TOAN','RD3P09'),
    ('LD3P09VAN','Lô bài thi DEMO VAN - phòng RD3P09','VAN','RD3P09'),
    ('LD3P09NN','Lô bài thi DEMO NN - phòng RD3P09','NN','RD3P09'),
    ('LD3P10TOAN','Lô bài thi DEMO TOAN - phòng RD3P10','TOAN','RD3P10'),
    ('LD3P10VAN','Lô bài thi DEMO VAN - phòng RD3P10','VAN','RD3P10'),
    ('LD3P10NN','Lô bài thi DEMO NN - phòng RD3P10','NN','RD3P10'),
    ('LD3P11TOAN','Lô bài thi DEMO TOAN - phòng RD3P11','TOAN','RD3P11'),
    ('LD3P11VAN','Lô bài thi DEMO VAN - phòng RD3P11','VAN','RD3P11'),
    ('LD3P11NN','Lô bài thi DEMO NN - phòng RD3P11','NN','RD3P11'),
    ('LD3P12TOAN','Lô bài thi DEMO TOAN - phòng RD3P12','TOAN','RD3P12'),
    ('LD3P12VAN','Lô bài thi DEMO VAN - phòng RD3P12','VAN','RD3P12'),
    ('LD3P12NN','Lô bài thi DEMO NN - phòng RD3P12','NN','RD3P12'),
    ('LD4P01TOAN','Lô bài thi DEMO TOAN - phòng RD4P01','TOAN','RD4P01'),
    ('LD4P01VAN','Lô bài thi DEMO VAN - phòng RD4P01','VAN','RD4P01'),
    ('LD4P01NN','Lô bài thi DEMO NN - phòng RD4P01','NN','RD4P01'),
    ('LD4P02TOAN','Lô bài thi DEMO TOAN - phòng RD4P02','TOAN','RD4P02'),
    ('LD4P02VAN','Lô bài thi DEMO VAN - phòng RD4P02','VAN','RD4P02'),
    ('LD4P02NN','Lô bài thi DEMO NN - phòng RD4P02','NN','RD4P02'),
    ('LD4P03TOAN','Lô bài thi DEMO TOAN - phòng RD4P03','TOAN','RD4P03'),
    ('LD4P03VAN','Lô bài thi DEMO VAN - phòng RD4P03','VAN','RD4P03'),
    ('LD4P03NN','Lô bài thi DEMO NN - phòng RD4P03','NN','RD4P03'),
    ('LD4P04TOAN','Lô bài thi DEMO TOAN - phòng RD4P04','TOAN','RD4P04'),
    ('LD4P04VAN','Lô bài thi DEMO VAN - phòng RD4P04','VAN','RD4P04'),
    ('LD4P04NN','Lô bài thi DEMO NN - phòng RD4P04','NN','RD4P04'),
    ('LD4P05TOAN','Lô bài thi DEMO TOAN - phòng RD4P05','TOAN','RD4P05'),
    ('LD4P05VAN','Lô bài thi DEMO VAN - phòng RD4P05','VAN','RD4P05'),
    ('LD4P05NN','Lô bài thi DEMO NN - phòng RD4P05','NN','RD4P05'),
    ('LD4P06TOAN','Lô bài thi DEMO TOAN - phòng RD4P06','TOAN','RD4P06'),
    ('LD4P06VAN','Lô bài thi DEMO VAN - phòng RD4P06','VAN','RD4P06'),
    ('LD4P06NN','Lô bài thi DEMO NN - phòng RD4P06','NN','RD4P06'),
    ('LD4P07TOAN','Lô bài thi DEMO TOAN - phòng RD4P07','TOAN','RD4P07'),
    ('LD4P07VAN','Lô bài thi DEMO VAN - phòng RD4P07','VAN','RD4P07'),
    ('LD4P07NN','Lô bài thi DEMO NN - phòng RD4P07','NN','RD4P07'),
    ('LD4P08TOAN','Lô bài thi DEMO TOAN - phòng RD4P08','TOAN','RD4P08'),
    ('LD4P08VAN','Lô bài thi DEMO VAN - phòng RD4P08','VAN','RD4P08'),
    ('LD4P08NN','Lô bài thi DEMO NN - phòng RD4P08','NN','RD4P08'),
    ('LD4P09TOAN','Lô bài thi DEMO TOAN - phòng RD4P09','TOAN','RD4P09'),
    ('LD4P09VAN','Lô bài thi DEMO VAN - phòng RD4P09','VAN','RD4P09'),
    ('LD4P09NN','Lô bài thi DEMO NN - phòng RD4P09','NN','RD4P09'),
    ('LD4P10TOAN','Lô bài thi DEMO TOAN - phòng RD4P10','TOAN','RD4P10'),
    ('LD4P10VAN','Lô bài thi DEMO VAN - phòng RD4P10','VAN','RD4P10'),
    ('LD4P10NN','Lô bài thi DEMO NN - phòng RD4P10','NN','RD4P10'),
    ('LD4P11TOAN','Lô bài thi DEMO TOAN - phòng RD4P11','TOAN','RD4P11'),
    ('LD4P11VAN','Lô bài thi DEMO VAN - phòng RD4P11','VAN','RD4P11'),
    ('LD4P11NN','Lô bài thi DEMO NN - phòng RD4P11','NN','RD4P11'),
    ('LD4P12TOAN','Lô bài thi DEMO TOAN - phòng RD4P12','TOAN','RD4P12'),
    ('LD4P12VAN','Lô bài thi DEMO VAN - phòng RD4P12','VAN','RD4P12'),
    ('LD4P12NN','Lô bài thi DEMO NN - phòng RD4P12','NN','RD4P12'),
    ('LD4P13TOAN','Lô bài thi DEMO TOAN - phòng RD4P13','TOAN','RD4P13'),
    ('LD4P13VAN','Lô bài thi DEMO VAN - phòng RD4P13','VAN','RD4P13'),
    ('LD4P13NN','Lô bài thi DEMO NN - phòng RD4P13','NN','RD4P13'),
    ('LD5P01TOAN','Lô bài thi DEMO TOAN - phòng RD5P01','TOAN','RD5P01'),
    ('LD5P01VAN','Lô bài thi DEMO VAN - phòng RD5P01','VAN','RD5P01'),
    ('LD5P01NN','Lô bài thi DEMO NN - phòng RD5P01','NN','RD5P01'),
    ('LD5P02TOAN','Lô bài thi DEMO TOAN - phòng RD5P02','TOAN','RD5P02'),
    ('LD5P02VAN','Lô bài thi DEMO VAN - phòng RD5P02','VAN','RD5P02'),
    ('LD5P02NN','Lô bài thi DEMO NN - phòng RD5P02','NN','RD5P02'),
    ('LD5P03TOAN','Lô bài thi DEMO TOAN - phòng RD5P03','TOAN','RD5P03'),
    ('LD5P03VAN','Lô bài thi DEMO VAN - phòng RD5P03','VAN','RD5P03'),
    ('LD5P03NN','Lô bài thi DEMO NN - phòng RD5P03','NN','RD5P03'),
    ('LD5P04TOAN','Lô bài thi DEMO TOAN - phòng RD5P04','TOAN','RD5P04'),
    ('LD5P04VAN','Lô bài thi DEMO VAN - phòng RD5P04','VAN','RD5P04'),
    ('LD5P04NN','Lô bài thi DEMO NN - phòng RD5P04','NN','RD5P04'),
    ('LD5P05TOAN','Lô bài thi DEMO TOAN - phòng RD5P05','TOAN','RD5P05'),
    ('LD5P05VAN','Lô bài thi DEMO VAN - phòng RD5P05','VAN','RD5P05'),
    ('LD5P05NN','Lô bài thi DEMO NN - phòng RD5P05','NN','RD5P05'),
    ('LD5P06TOAN','Lô bài thi DEMO TOAN - phòng RD5P06','TOAN','RD5P06'),
    ('LD5P06VAN','Lô bài thi DEMO VAN - phòng RD5P06','VAN','RD5P06'),
    ('LD5P06NN','Lô bài thi DEMO NN - phòng RD5P06','NN','RD5P06'),
    ('LD5P07TOAN','Lô bài thi DEMO TOAN - phòng RD5P07','TOAN','RD5P07'),
    ('LD5P07VAN','Lô bài thi DEMO VAN - phòng RD5P07','VAN','RD5P07'),
    ('LD5P07NN','Lô bài thi DEMO NN - phòng RD5P07','NN','RD5P07'),
    ('LD5P08TOAN','Lô bài thi DEMO TOAN - phòng RD5P08','TOAN','RD5P08'),
    ('LD5P08VAN','Lô bài thi DEMO VAN - phòng RD5P08','VAN','RD5P08'),
    ('LD5P08NN','Lô bài thi DEMO NN - phòng RD5P08','NN','RD5P08'),
    ('LD5P09TOAN','Lô bài thi DEMO TOAN - phòng RD5P09','TOAN','RD5P09'),
    ('LD5P09VAN','Lô bài thi DEMO VAN - phòng RD5P09','VAN','RD5P09'),
    ('LD5P09NN','Lô bài thi DEMO NN - phòng RD5P09','NN','RD5P09'),
    ('LD5P10TOAN','Lô bài thi DEMO TOAN - phòng RD5P10','TOAN','RD5P10'),
    ('LD5P10VAN','Lô bài thi DEMO VAN - phòng RD5P10','VAN','RD5P10'),
    ('LD5P10NN','Lô bài thi DEMO NN - phòng RD5P10','NN','RD5P10'),
    ('LD5P11TOAN','Lô bài thi DEMO TOAN - phòng RD5P11','TOAN','RD5P11'),
    ('LD5P11VAN','Lô bài thi DEMO VAN - phòng RD5P11','VAN','RD5P11'),
    ('LD5P11NN','Lô bài thi DEMO NN - phòng RD5P11','NN','RD5P11'),
    ('LD5P12TOAN','Lô bài thi DEMO TOAN - phòng RD5P12','TOAN','RD5P12'),
    ('LD5P12VAN','Lô bài thi DEMO VAN - phòng RD5P12','VAN','RD5P12'),
    ('LD5P12NN','Lô bài thi DEMO NN - phòng RD5P12','NN','RD5P12'),
    ('LD5P13TOAN','Lô bài thi DEMO TOAN - phòng RD5P13','TOAN','RD5P13'),
    ('LD5P13VAN','Lô bài thi DEMO VAN - phòng RD5P13','VAN','RD5P13'),
    ('LD5P13NN','Lô bài thi DEMO NN - phòng RD5P13','NN','RD5P13');
  -- Bai thi: BTD000001..BTD005250, phach XD0000001..XD0005250 (khac phach P... cua seed cu).
  -- Boi so 3: (ma th sinh - 1)*3 + mon (1 TOAN, 2 VAN, 3 NN).
  INSERT IGNORE INTO BaiThi (maBaiThi,maKyThi,maMonThi,maThiSinh,maLoBaiThi,soPhach,trangThaiCham)
  SELECT CONCAT('BTD',LPAD((CAST(SUBSTRING(t.maThiSinh,4) AS UNSIGNED)-1)*3+m.ord,6,'0')),
    'KTDEMO26',m.mon,t.maThiSinh,CONCAT('LD',SUBSTRING(p.maPhong,3),m.mon),
    CONCAT('XD',LPAD((CAST(SUBSTRING(t.maThiSinh,4) AS UNSIGNED)-1)*3+m.ord,7,'0')),'Chưa chấm'
  FROM ThiSinh t JOIN PhanPhongThi p ON p.maThiSinh=t.maThiSinh AND p.maPhanPhong=CONCAT('PPD',SUBSTRING(t.maThiSinh,4))
  CROSS JOIN (SELECT 1 AS ord,'TOAN' AS mon UNION ALL SELECT 2,'VAN' UNION ALL SELECT 3,'NN') m
  WHERE t.maThiSinh REGEXP '^TSD[0-9]{6}$' AND CAST(SUBSTRING(t.maThiSinh,4) AS UNSIGNED) BETWEEN 1 AND 1750;
  SELECT COUNT(*) INTO v_n FROM BaiThi WHERE maKyThi='KTDEMO26' AND maBaiThi LIKE 'BTD%';
  IF v_n <> 5250 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'So BaiThi moi khong bang 5250; rollback.'; END IF;
  -- DIEM: 0-10, buoc 0.25, diem gia lap nhung xac dinh (khong random moi lan).
  -- TOAN: 1750/1750 da cham; VAN: 1600/1750; NN: 1520/1750.
  INSERT IGNORE INTO KetQuaChamThi (maKetQuaChamThi,diemThi,nguoiNhap,thoiDiemNhap,trangThaiCongBo,maBaiThi)
  SELECT CONCAT('KQD',SUBSTRING(b.maBaiThi,4)),
    MOD(CAST(SUBSTRING(b.maThiSinh,4) AS UNSIGNED)*17 + CASE b.maMonThi WHEN 'TOAN' THEN 11 WHEN 'VAN' THEN 22 ELSE 33 END,41)*0.25,
    'Bộ dữ liệu DEMO (tạo tự động)','2026-06-13 10:00:00','Chưa công bố',b.maBaiThi
  FROM BaiThi b WHERE b.maKyThi='KTDEMO26' AND b.maBaiThi LIKE 'BTD%'
    AND (b.maMonThi='TOAN' OR (b.maMonThi='VAN' AND CAST(SUBSTRING(b.maThiSinh,4) AS UNSIGNED)<=1600)
      OR (b.maMonThi='NN' AND CAST(SUBSTRING(b.maThiSinh,4) AS UNSIGNED)<=1520));
  UPDATE BaiThi b INNER JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi
  SET b.trangThaiCham='Đã chấm' WHERE b.maKyThi='KTDEMO26' AND b.maBaiThi LIKE 'BTD%' AND b.trangThaiCham <> 'Đã chấm';
  SELECT COUNT(*) INTO v_n FROM KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maKyThi='KTDEMO26' AND b.maBaiThi LIKE 'BTD%';
  IF v_n <> 4870 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'So KetQuaChamThi moi khong bang 4870; rollback.'; END IF;
  SELECT COUNT(*) INTO v_n FROM BaiThi WHERE maKyThi='KTDEMO26' AND trangThaiCham='Chưa chấm';
  IF v_n <> 380 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'So bai chua cham phai 380; rollback.'; END IF;
  COMMIT;
END $$
DELIMITER ;
CALL sp_demo_1750_import_baithi();
DROP PROCEDURE IF EXISTS sp_demo_1750_import_baithi;

-- BAO CAO KIEM TRA SAU IMPORT - khong tinh bai thi va ket qua cu.
SELECT b.maKyThi, b.maMonThi,COUNT(*) AS tongBai,
 SUM(k.maKetQuaChamThi IS NOT NULL AND b.trangThaiCham='Đã chấm') AS daCham,
 SUM(k.maKetQuaChamThi IS NULL) AS chuaCham,
 ROUND(AVG(k.diemThi),2) AS diemTrungBinh
 FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi
 WHERE b.maKyThi='KTDEMO26' GROUP BY b.maKyThi,b.maMonThi ORDER BY b.maMonThi;
SELECT ts.maTruongTHCS,COUNT(DISTINCT ts.maThiSinh) AS thiSinh,
 COUNT(DISTINCT b.maBaiThi) AS baiThi,COUNT(DISTINCT k.maKetQuaChamThi) AS ketQua
 FROM ThiSinh ts LEFT JOIN BaiThi b ON b.maThiSinh=ts.maThiSinh AND b.maKyThi='KTDEMO26'
 LEFT JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi
 WHERE ts.maTruongTHCS LIKE 'THCS_DEMO%' GROUP BY ts.maTruongTHCS ORDER BY ts.maTruongTHCS;
SELECT COUNT(*) AS lichSuDiemMoi FROM LichSuChinhSuaDiem ls
 JOIN KetQuaChamThi k ON k.maKetQuaChamThi=ls.maKetQuaChamThi
 JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maKyThi='KTDEMO26';
-- LUU Y: 54 BaiThi, 51 KetQuaChamThi va lich su cu cung can duoc kiem tra rieng neu ban dang giu chung DB demo.

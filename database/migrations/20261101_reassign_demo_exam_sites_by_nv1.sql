-- Sửa dữ liệu demo cũ: thí sinh thi tại trường THPT nguyện vọng 1.
-- Có thể chạy lại an toàn sau khi đã nhập dreamteam_tuyensinh10_demo.sql.
START TRANSACTION;

UPDATE DiemThi
SET tenDiemThi = CASE maDiemThi
        WHEN 'DTD1' THEN 'Điểm thi Trường THPT Sao Mai'
        WHEN 'DTD2' THEN 'Điểm thi Trường THPT Bình Minh'
        WHEN 'DTD3' THEN 'Điểm thi Trường THPT Hòa An'
        WHEN 'DTD4' THEN 'Điểm thi Trường THPT Ánh Dương'
        WHEN 'DTD5' THEN 'Điểm thi Trường THPT Trần Quốc Khải'
    END,
    maTruongTHCS = NULL,
    maTruongTHPT = CASE maDiemThi
        WHEN 'DTD1' THEN 'THPT_1'
        WHEN 'DTD2' THEN 'THPT_2'
        WHEN 'DTD3' THEN 'THPT_3'
        WHEN 'DTD4' THEN 'THPT_4'
        WHEN 'DTD5' THEN 'THPT_5'
    END
WHERE maKyThi = 'KTDEMO26' AND maDiemThi IN ('DTD1', 'DTD2', 'DTD3', 'DTD4', 'DTD5');

INSERT INTO PhongThi (maPhong, tenPhong, sucChua, maDiemThi)
VALUES ('RD1P12', 'Phòng demo 1-12', 30, 'DTD1')
ON DUPLICATE KEY UPDATE tenPhong = VALUES(tenPhong), sucChua = VALUES(sucChua), maDiemThi = VALUES(maDiemThi);

DROP TEMPORARY TABLE IF EXISTS tmp_phan_phong_nv1;
CREATE TEMPORARY TABLE tmp_phan_phong_nv1 (
    maPhanPhong VARCHAR(20) PRIMARY KEY,
    maPhongMoi VARCHAR(20) NOT NULL
);

INSERT INTO tmp_phan_phong_nv1 (maPhanPhong, maPhongMoi)
SELECT ranked.maPhanPhong,
       CONCAT('RD', SUBSTRING_INDEX(ranked.maTruongTHPT, '_', -1), 'P', LPAD(CEIL(ranked.thuTu / 30), 2, '0'))
FROM (
    SELECT pp.maPhanPhong,
           nv.maTruongTHPT,
           ROW_NUMBER() OVER (
               PARTITION BY nv.maTruongTHPT
               ORDER BY SUBSTRING_INDEX(TRIM(REGEXP_REPLACE(t.hoTen, '[[:space:]]+', ' ')), ' ', -1)
                        COLLATE utf8mb4_vietnamese_ci,
                        t.hoTen COLLATE utf8mb4_vietnamese_ci,
                        t.maThiSinh
           ) AS thuTu
    FROM PhanPhongThi pp
    JOIN ThiSinh t ON t.maThiSinh = pp.maThiSinh
    JOIN NguyenVong nv ON nv.maThiSinh = t.maThiSinh AND nv.thuTuNguyenVong = 1
    JOIN PhongThi p ON p.maPhong = pp.maPhong
    JOIN DiemThi d ON d.maDiemThi = p.maDiemThi AND d.maKyThi = 'KTDEMO26'
    WHERE pp.trangThai = 'Đã phân'
) ranked;

UPDATE PhanPhongThi pp
JOIN tmp_phan_phong_nv1 x ON x.maPhanPhong = pp.maPhanPhong
JOIN PhongThi p ON p.maPhong = x.maPhongMoi
SET pp.maPhong = x.maPhongMoi,
    pp.ngayPhan = CURRENT_TIMESTAMP;

DROP TEMPORARY TABLE tmp_phan_phong_nv1;
COMMIT;

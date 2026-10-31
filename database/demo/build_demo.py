#!/usr/bin/env python3
"""Generate isolated, repeatable MariaDB demo imports from the supplied workbook.
Only needed to regenerate SQL; phpMyAdmin users can import the generated SQL files.
"""
from pathlib import Path
from collections import Counter, defaultdict
from datetime import datetime
import hashlib
import re
import openpyxl

ROOT = Path(__file__).resolve().parents[2]
OUT = Path(__file__).resolve().parent
SOURCE = OUT / 'source/DuLieuMau_5Truong_1750HocSinh.xlsx'
w = openpyxl.load_workbook(SOURCE, read_only=True, data_only=True)
def rows(sheet):
    return [tuple(r) for r in w[sheet].iter_rows(min_row=5, values_only=True) if r and r[0]]
schools, students, wishes, quotas = (rows(n) for n in ['Truong_THCS', 'ThiSinh', 'NguyenVong', 'ChiTieu_THPT'])
expected = dict(zip([f'THCS_DEMO{i}' for i in range(1,6)], [320,335,350,365,380]))
assert len(schools) == 5 and len(students) == 1750 and len(wishes) == 5250 and len(quotas) == 5
assert Counter(s[7] for s in students) == expected
for col in [0,1,4]:
    assert len({r[col] for r in students}) == 1750
assert {r[0] for r in schools} == set(expected)
assert {r[0] for r in quotas} == {f'THPT_{i}' for i in range(1,6)}
by_student = defaultdict(list)
for row in wishes:
    by_student[row[1]].append(row)
assert len({r[0] for r in wishes}) == 5250
assert set(by_student) == {r[0] for r in students}
for own in by_student.values():
    assert len(own) == 3 and {r[4] for r in own} == {1,2,3} and len({r[2] for r in own}) == 3
    assert all(r[2] in {q[0] for q in quotas} and r[3] == 'QD2026' for r in own)

def literal(value):
    if isinstance(value, datetime):
        value = value.strftime('%Y-%m-%d %H:%M:%S')
    if value is None: return 'NULL'
    if isinstance(value, (int, float)): return str(value)
    return "'" + str(value).replace('\\', '\\\\').replace("'", "''") + "'"
def insert(table, columns, data):
    return f'INSERT INTO {table} ({columns}) VALUES\n' + ',\n'.join('  (' + ', '.join(literal(v) for v in row) + ')' for row in data) + ';\n'
header = '-- Generated from the supplied synthetic workbook. ONLY for dreamteam_tuyensinh10_demo.\n'
base = (ROOT / 'database/install.sql').read_text()
assert base.count('CREATE DATABASE IF NOT EXISTS dreamteam_tuyensinh10 ') == 1
assert base.count('USE dreamteam_tuyensinh10;') == 1
base = base.replace('CREATE DATABASE IF NOT EXISTS dreamteam_tuyensinh10 ', 'CREATE DATABASE IF NOT EXISTS dreamteam_tuyensinh10_demo ').replace('USE dreamteam_tuyensinh10;', 'USE dreamteam_tuyensinh10_demo;')
(OUT / '00_install_demo.sql').write_text(header + '-- INITIALIZE AN EMPTY DEMO DATABASE ONCE. No DROP DATABASE/TABLE.\n' + base)
old_quotas = dict(re.findall(r"\('TT_(THPT_\d+)', (\d+),", (ROOT/'database/seed/seed.sql').read_text()))
assert len(old_quotas) == 5
q_rows = [(q[0], q[1], q[2], q[3], 'TT_'+q[0], int(old_quotas[q[0]])) for q in quotas]
sql = header + '''USE dreamteam_tuyensinh10_demo;
SET NAMES utf8mb4;
-- Temporary staging objects belong only to this import session.
DROP TEMPORARY TABLE IF EXISTS demo1750_schools;
DROP TEMPORARY TABLE IF EXISTS demo1750_students;
DROP TEMPORARY TABLE IF EXISTS demo1750_wishes;
DROP TEMPORARY TABLE IF EXISTS demo1750_quotas;
CREATE TEMPORARY TABLE demo1750_schools LIKE TruongTHCS;
CREATE TEMPORARY TABLE demo1750_students LIKE ThiSinh;
CREATE TEMPORARY TABLE demo1750_wishes LIKE NguyenVong;
CREATE TEMPORARY TABLE demo1750_quotas (
 maTruongTHPT VARCHAR(20) PRIMARY KEY, tenTruongTHPT VARCHAR(100), chiTieu INT,
 maDot VARCHAR(20), maThongTin VARCHAR(20), chiTieuGoc INT
);
'''
sql += insert('demo1750_schools', 'maTruongTHCS,tenTruongTHCS,diaChi,quanHuyen,maKhuVuc', schools)
sql += insert('demo1750_students', 'maThiSinh,maHoSo,hoTen,ngaySinh,soBaoDanh,dieuKienDuThi,trangThaiDangKyDuThi,maTruongTHCS', students)
sql += insert('demo1750_wishes', 'maNguyenVong,maThiSinh,maTruongTHPT,maQuyDinh,thuTuNguyenVong,ngayDangKy,trangThaiNguyenVong', wishes)
sql += insert('demo1750_quotas', 'maTruongTHPT,tenTruongTHPT,chiTieu,maDot,maThongTin,chiTieuGoc', q_rows)
sql += '''
DELIMITER $$
DROP PROCEDURE IF EXISTS demo1750_import$$
CREATE PROCEDURE demo1750_import()
BEGIN
 DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
 START TRANSACTION;
 IF DATABASE() <> 'dreamteam_tuyensinh10_demo' THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Import chi duoc chay tren database demo';
 END IF;
 IF (SELECT COUNT(*) FROM demo1750_schools)<>5 OR (SELECT COUNT(*) FROM demo1750_students)<>1750 OR (SELECT COUNT(*) FROM demo1750_wishes)<>5250 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Du lieu nguon khong du 5 truong/1750 hoc sinh/5250 nguyen vong';
 END IF;
 IF EXISTS(SELECT 1 FROM demo1750_schools s LEFT JOIN KhuVuc k ON k.maKhuVuc=s.maKhuVuc WHERE k.maKhuVuc IS NULL)
 OR EXISTS(SELECT 1 FROM demo1750_quotas q LEFT JOIN TruongTHPT p ON p.maTruongTHPT=q.maTruongTHPT LEFT JOIN DotTuyenSinh d ON d.maDot=q.maDot WHERE p.maTruongTHPT IS NULL OR d.maDot IS NULL OR p.tenTruongTHPT<>q.tenTruongTHPT)
 OR EXISTS(SELECT 1 FROM demo1750_wishes n LEFT JOIN QuyDinhNguyenVong q ON q.maQuyDinh=n.maQuyDinh WHERE q.maQuyDinh IS NULL OR n.thuTuNguyenVong>q.soLuongNguyenVongToiDa OR n.ngayDangKy NOT BETWEEN q.thoiGianBatDau AND q.thoiGianKetThuc) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Thieu du lieu nen hoac nguyen vong ngoai quy dinh';
 END IF;
 IF EXISTS(SELECT 1 FROM demo1750_students s LEFT JOIN demo1750_schools c ON c.maTruongTHCS=s.maTruongTHCS WHERE c.maTruongTHCS IS NULL)
 OR EXISTS(SELECT 1 FROM demo1750_wishes n LEFT JOIN demo1750_students s ON s.maThiSinh=n.maThiSinh LEFT JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT WHERE s.maThiSinh IS NULL OR p.maTruongTHPT IS NULL)
 OR EXISTS(SELECT 1 FROM demo1750_students s LEFT JOIN demo1750_wishes n ON n.maThiSinh=s.maThiSinh GROUP BY s.maThiSinh HAVING COUNT(n.maNguyenVong)<>3 OR COUNT(DISTINCT n.maTruongTHPT)<>3) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Khoa ngoai hoac bo 3 nguyen vong nguon khong hop le';
 END IF;
 -- Existing identities must match. Workflow statuses are deliberately not overwritten.
 IF EXISTS(SELECT 1 FROM demo1750_schools s JOIN TruongTHCS c ON c.maTruongTHCS=s.maTruongTHCS WHERE NOT(c.tenTruongTHCS<=>s.tenTruongTHCS) OR NOT(c.diaChi<=>s.diaChi) OR NOT(c.quanHuyen<=>s.quanHuyen) OR NOT(c.maKhuVuc<=>s.maKhuVuc))
 OR EXISTS(SELECT 1 FROM demo1750_students s JOIN ThiSinh t ON t.maThiSinh=s.maThiSinh WHERE t.maHoSo<>s.maHoSo OR t.hoTen<>s.hoTen OR t.ngaySinh<>s.ngaySinh OR NOT(t.soBaoDanh<=>s.soBaoDanh) OR t.maTruongTHCS<>s.maTruongTHCS)
 OR EXISTS(SELECT 1 FROM demo1750_students s JOIN ThiSinh t ON (t.maHoSo=s.maHoSo OR t.soBaoDanh=s.soBaoDanh) WHERE t.maThiSinh<>s.maThiSinh)
 OR EXISTS(SELECT 1 FROM demo1750_wishes s JOIN NguyenVong n ON n.maNguyenVong=s.maNguyenVong OR (n.maThiSinh=s.maThiSinh AND n.thuTuNguyenVong=s.thuTuNguyenVong) WHERE n.maNguyenVong<>s.maNguyenVong OR n.maThiSinh<>s.maThiSinh OR n.maTruongTHPT<>s.maTruongTHPT OR n.maQuyDinh<>s.maQuyDinh OR n.thuTuNguyenVong<>s.thuTuNguyenVong) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Trung ma voi du lieu khac; dung import, khong ghi de';
 END IF;
 IF (SELECT COUNT(*) FROM demo1750_quotas q JOIN ThongTinTuyenSinh t ON t.maThongTin=q.maThongTin AND t.maTruongTHPT=q.maTruongTHPT AND t.maDot=q.maDot)<>5
 OR EXISTS(SELECT 1 FROM demo1750_quotas q JOIN ThongTinTuyenSinh t ON t.maThongTin=q.maThongTin WHERE t.chiTieuTuyenSinh NOT IN(q.chiTieuGoc,q.chiTieu)) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Chi tieu nen da thay doi; dung import de tranh ghi de';
 END IF;
 INSERT INTO TruongTHCS SELECT s.* FROM demo1750_schools s WHERE NOT EXISTS(SELECT 1 FROM TruongTHCS c WHERE c.maTruongTHCS=s.maTruongTHCS);
 INSERT INTO ThiSinh SELECT s.* FROM demo1750_students s WHERE NOT EXISTS(SELECT 1 FROM ThiSinh t WHERE t.maThiSinh=s.maThiSinh);
 INSERT INTO NguyenVong SELECT s.* FROM demo1750_wishes s WHERE NOT EXISTS(SELECT 1 FROM NguyenVong n WHERE n.maNguyenVong=s.maNguyenVong);
 UPDATE ThongTinTuyenSinh t JOIN demo1750_quotas q ON t.maThongTin=q.maThongTin AND t.maTruongTHPT=q.maTruongTHPT AND t.maDot=q.maDot SET t.chiTieuTuyenSinh=q.chiTieu;
 COMMIT;
END$$
CALL demo1750_import()$$
DROP PROCEDURE demo1750_import$$
DELIMITER ;
-- Session staging disappears on disconnect. No persistent rows are deleted.
SELECT c.maTruongTHCS,c.tenTruongTHCS,COUNT(t.maThiSinh) AS soHocSinh
FROM TruongTHCS c LEFT JOIN ThiSinh t ON t.maTruongTHCS=c.maTruongTHCS
WHERE c.maTruongTHCS IN('THCS_DEMO1','THCS_DEMO2','THCS_DEMO3','THCS_DEMO4','THCS_DEMO5')
GROUP BY c.maTruongTHCS,c.tenTruongTHCS ORDER BY c.maTruongTHCS;
'''
(OUT/'01_import_1750.sql').write_text(sql)
(OUT/'SOURCE.md').write_text(f'''# Nguồn dữ liệu\n\nWorkbook người dùng cung cấp: `{SOURCE.name}`. SHA-256: `{hashlib.sha256(SOURCE.read_bytes()).hexdigest()}`. Các sheet xác nhận đây là dữ liệu mô phỏng, không phải học sinh thật.\n\nNguồn chính: 5 dòng Truong_THCS, 1.750 dòng ThiSinh, 5.250 dòng NguyenVong, 5 dòng ChiTieu_THPT. Mã, tên tiếng Việt, ngày sinh, số báo danh và nguyện vọng được giữ theo Excel. ZIP được dùng để đối chiếu, không chạy trực tiếp vì các INSERT trong đó chưa idempotent.\n\nTạo lại SQL (chỉ khi thay nguồn/schema): cài `openpyxl`, chạy `python3 database/demo/build_demo.py`. Không đổi bộ seed nhỏ của dự án.\n''')
print('Generated isolated initializer and idempotent import:',len(students),'students,',len(wishes),'wishes')

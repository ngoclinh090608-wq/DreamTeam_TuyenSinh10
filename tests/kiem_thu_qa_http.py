#!/usr/bin/env python3
"""HTTP regression against committed fixtures owned by kiem_thu_qa_regression.php."""
import html
import http.cookiejar
import os
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request

base = os.environ.get('BASE_URL', 'http://127.0.0.1:8080').rstrip('/')
db_name = os.environ.get('DB_NAME', '')
assert re.fullmatch(r'qa_dt_[a-z0-9_]+', db_name), 'QA database required'

def sql(statement):
    env = os.environ.copy()
    env['MYSQL_PWD'] = env.get('DB_PASS', '')
    args = ['mysql', '-h', env.get('DB_HOST', '127.0.0.1'), '-P', env.get('DB_PORT', '3306'), '-u', env.get('DB_USER', 'root'), '--default-character-set=utf8mb4', '-N', db_name]
    # MySQL's batch client requires a statement terminator; MariaDB also accepts EOF.
    batch = 'SET NAMES utf8mb4;\nSET autocommit=1;\n' + statement.rstrip().rstrip(';') + ';\nCOMMIT;\n'
    return subprocess.run(args, input=batch, text=True, capture_output=True, check=True, env=env).stdout.strip()

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(opener, path, data=None):
    payload = None if data is None else urllib.parse.urlencode(data, doseq=True).encode()
    try:
        response = opener.open(base + path, payload)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode(), response.url

def token(page):
    return html.unescape(re.search(r'name="csrf_token" value="([^"]+)"', page)[1])

def login(name, password='qa-test-password'):
    opener = client()
    _, page, _ = request(opener, '/auth/login.php')
    status, page, _ = request(opener, '/auth/login.php', {'csrf_token': token(page), 'tenDangNhap': name, 'matKhau': password})
    assert status == 200 and 'Đăng xuất' in page
    return opener

_, _, location = request(client(), '/results/')
assert '/auth/login.php' in location
student = login('qa_student_a')
assert request(student, '/candidates/cap-so-bao-danh.php')[0] == 403
assert request(student, '/enrollment/update.php')[0] == 403
assert request(student, '/enrollment/')[0] == 200
sql("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Đã phê duyệt',trangThaiCongBo='Đã công bố' WHERE maDanhSach='QA_LIST'")
publication_state = sql("SELECT CONCAT(trangThaiPheDuyet, '|', trangThaiCongBo) FROM DanhSachKetQuaTuyenSinh WHERE maDanhSach='QA_LIST'")
assert publication_state == 'Đã phê duyệt|Đã công bố', ('publication fixture not committed as UTF-8', db_name, publication_state)
for path in ['/results/?maThiSinh=QA_B&maKetQua=QA_RB', '/enrollment/?maThiSinh=QA_B&maKetQua=QA_RB&nam=2090']:
    status, page, _ = request(student, path)
    assert status == 200 and 'Trường THPT Mẫu 1' in page and 'Trường THPT Mẫu 2' not in page, (path, status, re.sub(r'<[^>]*>', ' ', page)[-3500:])
_, detail, _ = request(student, '/results/?ketQua=QA_RA')
assert 'Chi tiết' in detail and 'Quay lại' in detail
assert 'Không tìm thấy kết quả' in request(student,'/results/?ketQua=QA_RB')[1]
sql("DELETE FROM KetQuaTuyenSinh WHERE maKetQua='QA_LA'")
assert 'Không tìm thấy kết quả xét tuyển.' in request(student,'/results/')[1]
sql("INSERT INTO KetQuaTuyenSinh(maKetQua,maDanhSach,maKetQuaXetTuyen) VALUES('QA_LA','QA_LIST','QA_RA')")
print('PASS F03 HTTP: own result detail, back link, foreign detail denied, published missing result')
assert "number_format(" not in request(login('hdtuyensinh', 'dreamteam2026'), '/enrollment/?nam=2090')[1]
print('PASS HTTP UC04/19: published ownership, forged owner ignored, student enrollment, staff markup')
sql("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Chưa phê duyệt',trangThaiCongBo='Chưa công bố' WHERE maDanhSach='QA_LIST'")
assert 'Kết quả xét tuyển chưa được công bố.' in request(student,'/results/')[1]
_, page, _ = request(student, '/appeals/')
form = {'action': 'submit', 'paper': 'QA_PA2', 'reason': '<script>alert(1)</script>', 'csrf_token': 'bad'}
_, denied, _ = request(student, '/appeals/', form)
assert 'không hợp lệ' in denied
assert sql("SELECT COUNT(*) FROM YeuCauPhucKhao WHERE maBaiThi='QA_PA2'") == '0'
form['csrf_token'] = token(page)
form['paper'] = 'QA_PB1'
assert 'thuộc hồ sơ' in request(student, '/appeals/', form)[1]
form['paper'] = 'QA_PA2'
_, saved, _ = request(student, '/appeals/', form)
assert 'Gửi yêu cầu phúc khảo thành công' in saved and '&lt;script&gt;' in saved and '<script>alert(1)</script>' not in saved
assert 'đã có yêu cầu' in request(student, '/appeals/', form)[1]
req_id = sql("SELECT maYeuCau FROM YeuCauPhucKhao WHERE maBaiThi='QA_PA2'")
assert req_id not in request(login('qa_student_b'), '/appeals/')[1]
print('PASS HTTP UC18: CSRF, ownership, persisted request, duplicate, output escaping and isolation')
council = login('qa_council')
_, page, _ = request(council, '/appeals/')
form = {'action': 'complete', 'request': req_id, 'score': '8.5', 'conclusion': 'Rechecked', 'evidence': 'Checked scan', 'minutes': 'Council approved', 'csrf_token': 'bad'}
assert 'không hợp lệ' in request(council, '/appeals/', form)[1]
assert sql("SELECT COUNT(*) FROM KetQuaPhucKhao WHERE maYeuCau='" + req_id + "'") == '0'
form['csrf_token'] = token(page)
_, saved, _ = request(council, '/appeals/', form)
assert 'Đã lưu kết quả' in saved and '8.50' in saved
assert 'đã được xử lý' in request(council, '/appeals/', form)[1]
assert '8.50' in request(student, '/results/')[1]
print('PASS HTTP UC16: assigned council, CSRF, persisted score, duplicate rejection, UC04 reflects final score')
admin = login('bantuyensinh', 'dreamteam2026')
_, page, _ = request(admin, '/candidates/cap-so-bao-danh.php')
form = {'csrf_token': 'bad', 'candidates[]': ['QA_D'], 'confirm': '1'}
assert 'không hợp lệ' in request(admin, '/candidates/cap-so-bao-danh.php', form)[1]
assert sql("SELECT soBaoDanh IS NULL FROM ThiSinh WHERE maThiSinh='QA_D'") == '1'
form['csrf_token'] = token(page)
assert 'Đã cấp số báo danh cho 1 thí sinh' in request(admin, '/candidates/cap-so-bao-danh.php', form)[1]
assert 'đã có số báo danh' in request(admin, '/candidates/cap-so-bao-danh.php', form)[1]
print('PASS HTTP UC03: staff role, CSRF, actual batch submission and repeat rejected')

_, current, _ = request(admin, '/candidates/cap-so-bao-danh.php')
assert 'scopeFingerprint' in current and 'chưa có SBD' in current
csv = request(admin, '/candidates/cap-so-bao-danh.php?trangThai=issued&export=1')
assert csv[0] == 200 and 'QA_D' in csv[1] and 'Số báo danh' in csv[1]
assert request(student, '/candidates/cap-so-bao-danh.php?export=1')[0] == 403
_, denied, _ = request(admin, '/candidates/cap-so-bao-danh.php', {'csrf_token':token(current),'candidates[]':['QA_E']})
assert 'Vui lòng xác nhận' in denied
_, denied, _ = request(admin, '/candidates/cap-so-bao-danh.php', {'csrf_token':token(current),'action':'issue_scope','confirm':'1','expectedCount':'0','scopeFingerprint':'forged'})
assert 'Số hồ sơ đã thay đổi' in denied
print('PASS F02 HTTP: totals, export, export role, confirmation and scope change protection')
# UC21/22 routes: authenticate with the exact actors and reject writes before touching state.
assert '/auth/login.php' in request(client(), '/publication/tuyen-sinh.php')[2]
assert request(student, '/publication/tuyen-sinh.php')[0] == 403
assert request(admin, '/publication/tuyen-sinh.php')[0] == 403
assert request(student, '/statistics/')[0] == 403
status, stats, _ = request(admin, '/statistics/?nam=2090&loai=ketqua')
assert status == 200 and 'Trúng tuyển' in stats
status, csv, _ = request(admin, '/statistics/?nam=2090&loai=ketqua&export=csv')
assert status == 200 and 'Trúng tuyển' in csv
publication = login('hdtuyensinh', 'dreamteam2026')
assert request(publication, '/statistics/')[0] == 403
_, page, _ = request(publication, '/publication/tuyen-sinh.php?maDanhSach=QA_LIST')
post = {'action':'cong_bo','maDanhSach':'QA_LIST','csrf_token':'bad'}
assert 'không hợp lệ' in request(publication, '/publication/tuyen-sinh.php', post)[1]
post['csrf_token'] = token(page)
assert 'chưa được phê duyệt' in request(publication, '/publication/tuyen-sinh.php', post)[1]
sql("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Đã phê duyệt' WHERE maDanhSach='QA_LIST'; UPDATE KetQuaXetTuyen SET diemXetTuyen=NULL WHERE maKetQua='QA_RA'")
try:
    assert 'Dữ liệu kết quả xét tuyển chưa đầy đủ hoặc chưa hợp lệ: QA_RA' in request(publication, '/publication/tuyen-sinh.php', post)[1]
    assert sql("SELECT trangThaiCongBo FROM DanhSachKetQuaTuyenSinh WHERE maDanhSach='QA_LIST'") == 'Chưa công bố'
finally:
    sql("UPDATE KetQuaXetTuyen SET diemXetTuyen=24 WHERE maKetQua='QA_RA'; UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Chưa phê duyệt' WHERE maDanhSach='QA_LIST'")
print('PASS F06/F07 HTTP: exact roles, read-only statistics/CSV, UC22 login, CSRF, approval and invalid-record blocks')

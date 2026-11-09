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
    return subprocess.run(args, input=statement, text=True, capture_output=True, check=True, env=env).stdout.strip()

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
for path in ['/results/?maThiSinh=QA_B&maKetQua=QA_RB', '/enrollment/?maThiSinh=QA_B&maKetQua=QA_RB&nam=2090']:
    status, page, _ = request(student, path)
    assert status == 200 and 'Trường THPT Mẫu 1' in page and 'Trường THPT Mẫu 2' not in page
assert "number_format(" not in request(login('hdtuyensinh', 'dreamteam2026'), '/enrollment/?nam=2090')[1]
print('PASS HTTP UC04/19: published ownership, forged owner ignored, student enrollment, staff markup')
sql("UPDATE DanhSachKetQuaTuyenSinh SET trangThaiPheDuyet='Chưa phê duyệt',trangThaiCongBo='Chưa công bố' WHERE maDanhSach='QA_LIST'")
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
form = {'csrf_token': 'bad', 'candidates[]': ['QA_D']}
assert 'không hợp lệ' in request(admin, '/candidates/cap-so-bao-danh.php', form)[1]
assert sql("SELECT soBaoDanh IS NULL FROM ThiSinh WHERE maThiSinh='QA_D'") == '1'
form['csrf_token'] = token(page)
assert 'Đã cấp số báo danh cho 1 thí sinh' in request(admin, '/candidates/cap-so-bao-danh.php', form)[1]
assert 'đã có số báo danh' in request(admin, '/candidates/cap-so-bao-danh.php', form)[1]
print('PASS HTTP UC03: staff role, CSRF, actual batch submission and repeat rejected')

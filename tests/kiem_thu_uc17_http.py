#!/usr/bin/env python3
"""Called by the UC17 PHP test while its isolated fixtures exist."""
import html
import http.cookiejar
import os
import re
import urllib.error
import urllib.parse
import urllib.request

base = os.environ.get('BASE_URL', 'http://127.0.0.1:8080').rstrip('/')
def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(opener, path, data=None):
    payload = None if data is None else urllib.parse.urlencode(data).encode()
    try:
        response = opener.open(base + path, payload)
    except urllib.error.HTTPError as e:
        response = e
    return response.status, response.read().decode(), response.url
def token(body):
    return html.unescape(re.search(r'name="csrf_token" value="([^"]+)"', body)[1])
def login(name):
    opener = client()
    _, page, _ = request(opener, '/auth/login.php')
    status, page, _ = request(opener, '/auth/login.php', {'csrf_token': token(page), 'tenDangNhap': name, 'matKhau': 'dreamteam2026'})
    assert status == 200 and 'Đăng xuất' in page
    return opener

_, _, location = request(client(), '/admission/')
assert '/auth/login.php' in location
student = login('SBD0001')
assert request(student, '/admission/')[0] == 403
council = login('hdtuyensinh')
status, page, _ = request(council, '/admission/?exam=U17_EXAM')
assert status == 200 and 'Cấu hình xét tuyển' in page and 'U17_A' in page
form = {'exam': 'U17_EXAM', 'action': 'preview', 'closed': '1', 'appealsEnded': '1'}
for subject in ['TOAN', 'VAN', 'NN']:
    form[f'weights[{subject}]'] = '1'
for school, selected in [('A', 1), ('B', 2), ('C', 3)]:
    for nv in [1, 2, 3]:
        form[f'allocation[U17_{school}][{nv}]'] = str(int(nv == selected))
form['csrf_token'] = 'invalid'
_, denied, _ = request(council, '/admission/', form)
assert 'yêu cầu không hợp lệ' in denied
form['csrf_token'] = token(page)
status, preview, _ = request(council, '/admission/', form)
assert status == 200 and '3 thí sinh dự kiến trúng tuyển' in preview
assert 'name="preview"' in preview and 'Lưu kết quả xét tuyển chính thức' in preview
assert 'Trúng tuyển' in preview and 'U17_TS3' in preview
save_nonce = html.unescape(re.search(r'name="preview" value="([^"]+)"', preview)[1])
# Tampered/absent confirmation must not write results.
_, denied, _ = request(council, '/admission/', {'action': 'save', 'exam': 'U17_EXAM', 'csrf_token': token(preview), 'preview': save_nonce})
assert 'Vui lòng xác nhận đã kiểm tra' in denied
print('PASS UC17 HTTP: login, role, CSRF, allocation form, preview, confirmation required')

if os.environ.get('UC17_HTTP_SAVE') == '1':
    _, preview, _ = request(council, '/admission/', form)
    nonce = html.unescape(re.search(r'name="preview" value="([^"]+)"', preview)[1])
    status, saved, _ = request(council, '/admission/', {'action': 'save', 'exam': 'U17_EXAM', 'csrf_token': token(preview), 'preview': nonce, 'confirm': '1'})
    assert status == 200 and 'Xét tuyển thành công' in saved
    assert 'Lịch sử xét tuyển' in saved
    _, repeated, _ = request(council, '/admission/', {'action': 'save', 'exam': 'U17_EXAM', 'csrf_token': token(saved), 'preview': nonce, 'confirm': '1'})
    assert 'Bản xem trước đã hết hạn' in repeated
    print('PASS UC17 HTTP: confirmed save, history, duplicate submission rejected')

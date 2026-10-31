#!/usr/bin/env python3
"""Follow rendered UC05 links; validate uploads without saving or changing seed data."""
import html.parser
import http.cookiejar
import io
import os
import re
import urllib.parse
import urllib.request
import zipfile

base = os.environ.get('BASE_URL', 'http://127.0.0.1:8080').rstrip('/') + '/'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

class Links(html.parser.HTMLParser):
    def __init__(self, markup):
        super().__init__()
        self.links = []
        self.feed(markup)
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'a' and 'href' in attrs:
            self.links.append(attrs['href'])

def request(path, data=None, content_type=None):
    headers = {'Content-Type': content_type} if content_type else {}
    return client.open(urllib.request.Request(urllib.parse.urljoin(base, path), data=data, headers=headers)).read()

def field(markup, name):
    return re.search(r'name="' + name + r'" value="([^"]+)"', markup)[1]

login = request('auth/login.php').decode()
request('auth/login.php', urllib.parse.urlencode(dict(csrf_token=field(login, 'csrf_token'), tenDangNhap='hdchamthi', matKhau=os.environ.get('MAT_KHAU_DEMO', 'dreamteam2026'))).encode())
for subject, room, bag in [('NN', 'P101', 'LO_P101_NN'), ('TOAN', 'P202', 'LO_P202_TOAN')]:
    page = request('grading/nhap-diem.php?' + urllib.parse.urlencode(dict(kyThi='KT2026', monThi=subject, phongThi=room))).decode()
    selection = next(link for link in Links(page).links if urllib.parse.parse_qs(urllib.parse.urlsplit(link).query).get('tuiBai') == [bag])
    selected = request(selection).decode()
    assert 'id="ket-qua-diem"' in selected and 'id="excel"' in selected
    assert bag in selected
    print('PASS select actual bag link, import section and score table:', bag)
    links = Links(selected).links
    report = next(link for link in links if 'loaiFile=bang_diem' in link)
    with zipfile.ZipFile(io.BytesIO(request(report))) as archive:
        sheet = archive.read('xl/worksheets/sheet1.xml').decode()
        assert room in sheet and 'Số phách' in sheet
        if subject == 'NN':
            assert '<t>9.50</t>' in sheet or re.search(r'<t>\d+\.\d{2}</t>', sheet)
            assert 'Túi này đã có đủ điểm' in selected
            print('PASS fully graded bag still downloads recorded scores')
    if subject == 'TOAN':
        download = next(link for link in links if 'download=1' in link and 'loaiFile=' not in link)
        original = request(download)
        filled = io.BytesIO()
        with zipfile.ZipFile(io.BytesIO(original)) as source, zipfile.ZipFile(filled, 'w', zipfile.ZIP_DEFLATED) as target:
            for name in source.namelist():
                data = source.read(name)
                if name == 'xl/worksheets/sheet1.xml':
                    data = re.sub(rb'(<c r="C[2-9][0-9]*" t="inlineStr"><is><t>)(</t>)', rb'\g<1>8.75\2', data)
                target.writestr(name, data)
        boundary = '----UC05NavigationTest'
        parts = []
        fields = dict(csrf_token=field(selected, 'csrf_token'), action='kiem_tra_excel', kyThi='KT2026', monThi=subject, phongThi=room, tuiBai=bag)
        for key, value in fields.items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
        parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="excel"; filename="tui.xlsx"\r\nContent-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\r\n\r\n'.encode() + filled.getvalue() + f'\r\n--{boundary}--\r\n'.encode())
        preview = request(selection, b''.join(parts), 'multipart/form-data; boundary=' + boundary).decode()
        assert 'Xác nhận lưu 3 điểm' in preview
        print('PASS actual download can be filled and uploaded for confirmation; no scores saved')

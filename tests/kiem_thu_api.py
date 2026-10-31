#!/usr/bin/env python3
"""HTTP integration checks for the PHP REST API; no database rows are changed."""
import html
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request

base = os.environ.get('BASE_URL', 'http://127.0.0.1:8080').rstrip('/') + '/'


def client():
    return urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
    )


def call(opener, path, method='GET', body=None, headers=None):
    payload = None if body is None else json.dumps(body).encode()
    request_headers = dict(headers or {})
    if payload is not None:
        request_headers['Content-Type'] = 'application/json'
    request = urllib.request.Request(
        urllib.parse.urljoin(base, path), data=payload,
        headers=request_headers, method=method,
    )
    try:
        response = opener.open(request)
    except urllib.error.HTTPError as error:
        response = error
    raw = response.read().decode()
    return response.status, json.loads(raw), response.headers


anonymous = client()
status, schools, _ = call(anonymous, 'api/v1/schools')
assert status == 200 and schools['success'] is True and schools['data']
assert {'id', 'name', 'region', 'quota'} <= schools['data'][0].keys()

status, exams, _ = call(anonymous, 'api/v1/exams')
assert status == 200 and exams['success'] is True and exams['data']
exam_id = exams['data'][0]['maKyThi']

status, sites, _ = call(anonymous, f'api/v1/exams/{urllib.parse.quote(exam_id)}/sites')
assert status == 200 and sites['success'] is True and sites['data']
site_id = sites['data'][0]['maDiemThi']

status, rooms, _ = call(anonymous, f'api/v1/exam-sites/{urllib.parse.quote(site_id)}/rooms')
assert status == 200 and rooms['success'] is True and isinstance(rooms['data'], list)

status, denied, _ = call(anonymous, f'api/v1/exams/{exam_id}/room-preparations', 'POST', {'selection': site_id})
assert status == 401 and denied['error']['code'] == 'AUTHENTICATION_REQUIRED'

status, method_error, method_headers = call(anonymous, 'api/v1/schools', 'POST', {})
assert status == 405 and method_error['error']['code'] == 'METHOD_NOT_ALLOWED'
assert method_headers.get('Allow') == 'GET'

staff = client()
login_page = staff.open(urllib.parse.urljoin(base, 'auth/login.php')).read().decode()
login_token = html.unescape(re.search(r'name="csrf_token" value="([^"]+)"', login_page)[1])
login_form = urllib.parse.urlencode({
    'csrf_token': login_token,
    'tab': 'admin',
    'tenDangNhap': 'bantuyensinh',
    'matKhau': os.environ.get('MAT_KHAU_DEMO', 'dreamteam2026'),
}).encode()
logged_in = staff.open(urllib.request.Request(
    urllib.parse.urljoin(base, 'auth/login.php'), data=login_form,
    headers={'Content-Type': 'application/x-www-form-urlencoded'},
)).read().decode()
csrf = html.unescape(re.search(r'name="csrf_token" value="([^"]+)"', logged_in)[1])

status, csrf_error, _ = call(
    staff, f'api/v1/exams/{exam_id}/room-preparations', 'POST', {'selection': site_id},
    {'X-CSRF-Token': 'invalid'},
)
assert status == 419 and csrf_error['error']['code'] == 'INVALID_CSRF_TOKEN'

status, validation, _ = call(
    staff, f'api/v1/exams/{exam_id}/room-preparations', 'POST', {'selection': ''},
    {'X-CSRF-Token': csrf},
)
assert status == 422 and validation['error']['code'] == 'VALIDATION_FAILED'

status, missing, _ = call(anonymous, 'api/v1/not-found')
assert status == 404 and missing['error']['code'] == 'NOT_FOUND'

print('PASS REST API catalogues, routing, JSON errors, session roles and CSRF; no rows changed')

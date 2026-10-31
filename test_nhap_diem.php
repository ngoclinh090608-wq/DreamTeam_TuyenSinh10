<?php
$c = curl_init("http://127.0.0.1:8080/auth/login.php");
curl_setopt_array($c, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => __DIR__ . '/test_cookie.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/test_cookie.txt',
]);
$html = curl_exec($c);
preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m);
$tok = $m[1] ?? '';
echo "Token: $tok\n";

curl_setopt_array($c, [
    CURLOPT_URL => "http://127.0.0.1:8080/auth/login.php",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf_token' => $tok,
        'tab' => 'admin',
        'tenDangNhap' => 'hdchamthi',
        'matKhau' => '1234',
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);
$loginRes = curl_exec($c);
echo "After login URL: " . curl_getinfo($c, CURLINFO_EFFECTIVE_URL) . "\n";

curl_setopt_array($c, [
    CURLOPT_HTTPGET => true,
    CURLOPT_URL => "http://127.0.0.1:8080/grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN",
]);
$res = curl_exec($c);
echo "HTTP code: " . curl_getinfo($c, CURLINFO_HTTP_CODE) . "\n";
echo "Response length: " . strlen($res) . "\n";
if (strpos($res, 'Nhập điểm thi') !== false) {
    echo "Found 'Nhập điểm thi'!\n";
} else {
    echo "NOT found! First 500 chars:\n" . substr($res, 0, 500) . "\n";
}


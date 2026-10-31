<?php
declare(strict_types=1);

/** REST API v1: a thin HTTP layer over the existing application services. */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/live_data.php';
require_once dirname(__DIR__) . '/services/exam_service.php';

final class ApiException extends RuntimeException
{
    /** @param string[] $details */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
        public readonly array $headers = []
    ) {
        parent::__construct($message);
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/** @param array<string,mixed> $body */
function api_json(int $status, array $body, array $headers = []): never {
    http_response_code($status);
    foreach ($headers as $name => $value) header($name . ': ' . $value);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
    exit;
}

function api_success(mixed $data, int $status = 200): never {
    api_json($status, ['success' => true, 'data' => $data]);
}

function api_fail(ApiException $error): never {
    $payload = [
        'success' => false,
        'error' => ['code' => $error->errorCode, 'message' => $error->getMessage()],
    ];
    if ($error->details) $payload['error']['details'] = $error->details;
    api_json($error->status, $payload, $error->headers);
}

/** @return string[] */
function api_segments(): array {
    $path = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''));
    $marker = '/api/v1';
    $offset = stripos($path, $marker);
    if ($offset === false) throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy API được yêu cầu.');
    $relative = trim(substr($path, $offset + strlen($marker)), '/');
    return $relative === '' ? [] : explode('/', $relative);
}

function api_identifier(string $value, string $label): string {
    if ($value === '' || strlen($value) > 80 || !preg_match('/^[A-Za-z0-9:_-]+$/', $value)) {
        throw new ApiException(400, 'INVALID_PARAMETER', $label . ' không hợp lệ.');
    }
    return $value;
}

/** @return array<string,mixed> */
function api_body(): array {
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
        throw new ApiException(413, 'PAYLOAD_TOO_LARGE', 'Nội dung yêu cầu vượt quá giới hạn 16 KB.');
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    try {
        $body = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new ApiException(400, 'INVALID_JSON', 'Nội dung JSON không hợp lệ.');
    }
    if (!is_array($body) || array_is_list($body)) {
        throw new ApiException(400, 'INVALID_JSON', 'Nội dung JSON phải là một object.');
    }
    return $body;
}

/** @param string[] $roles */
function api_require_roles(array $roles): array {
    $user = current_user();
    if ($user === null) throw new ApiException(401, 'AUTHENTICATION_REQUIRED', 'Vui lòng đăng nhập để sử dụng API này.');
    if (!has_role($user, $roles)) throw new ApiException(403, 'FORBIDDEN', TB_KHONG_CO_QUYEN);
    return $user;
}

function api_require_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!csrf_verify_token(is_string($token) ? $token : null)) {
        throw new ApiException(419, 'INVALID_CSRF_TOKEN', 'Phiên làm việc đã hết hạn hoặc CSRF token không hợp lệ.');
    }
}

function api_method(string $expected): void {
    $actual = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($actual !== $expected) {
        throw new ApiException(405, 'METHOD_NOT_ALLOWED', 'HTTP method không được hỗ trợ cho tài nguyên này.', [], ['Allow' => $expected]);
    }
}

try {
    $segments = api_segments();

    if ($segments === ['schools']) {
        api_method('GET');
        $schools = live_home_schools();
        if ($schools === null) throw new ApiException(503, 'DATABASE_UNAVAILABLE', 'Không thể tải danh sách trường tại thời điểm hiện tại.');
        api_success(array_map(static fn(array $row): array => [
            'id' => $row[0], 'name' => $row[1], 'region' => $row[2], 'quota' => (int)$row[3],
        ], $schools));
    }

    if ($segments === ['exams']) {
        api_method('GET');
        api_success(exam_ds_ky_thi());
    }

    if (count($segments) === 3 && $segments[0] === 'exams' && $segments[2] === 'sites') {
        api_method('GET');
        $examId = api_identifier($segments[1], 'Mã kỳ thi');
        api_success(exam_ds_diem_thi($examId));
    }

    if (count($segments) === 3 && $segments[0] === 'exam-sites' && $segments[2] === 'rooms') {
        api_method('GET');
        $siteId = api_identifier($segments[1], 'Mã điểm thi');
        api_success(exam_ds_phong_thi($siteId));
    }

    if (count($segments) === 3 && $segments[0] === 'exams' && $segments[2] === 'room-preparations') {
        api_method('POST');
        api_require_roles(['BAN_TUYEN_SINH', 'QUAN_TRI']);
        api_require_csrf();
        $examId = api_identifier($segments[1], 'Mã kỳ thi');
        $body = api_body();
        $selection = is_string($body['selection'] ?? null) ? trim($body['selection']) : '';
        if ($selection === '') throw new ApiException(422, 'VALIDATION_FAILED', 'Vui lòng chọn một điểm thi cần tạo phòng.');
        api_identifier($selection, 'Điểm thi đã chọn');
        api_success(exam_tao_diem_thi_theo_nv1($examId, $selection), 201);
    }

    throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy API được yêu cầu.');
} catch (ApiException $e) {
    api_fail($e);
} catch (BusinessException $e) {
    api_fail(new ApiException(422, 'BUSINESS_RULE_VIOLATION', $e->getMessage(), $e->details()));
} catch (PDOException $e) {
    error_log('REST API database: ' . $e->getMessage());
    api_fail(new ApiException(503, 'DATABASE_UNAVAILABLE', 'Không thể kết nối cơ sở dữ liệu tại thời điểm hiện tại.'));
} catch (Throwable $e) {
    error_log('REST API: ' . $e->getMessage());
    api_fail(new ApiException(500, 'INTERNAL_ERROR', 'Không thể xử lý yêu cầu tại thời điểm hiện tại.'));
}

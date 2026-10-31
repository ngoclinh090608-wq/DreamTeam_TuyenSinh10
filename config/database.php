<?php
declare(strict_types=1);
/** Shared PDO connection; environment variables override optional local settings. */
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    // Optional, gitignored local binding, e.g. to the isolated demo database.
    $localPath = __DIR__ . '/database.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) throw new RuntimeException('database.local.php must return an array');
    $host = getenv('DB_HOST') ?: ($local['host'] ?? '127.0.0.1');
    $port = getenv('DB_PORT') ?: ($local['port'] ?? '3306');
    $name = getenv('DB_NAME') ?: ($local['name'] ?? 'dreamteam_tuyensinh10_demo');
    $user = getenv('DB_USER') ?: ($local['user'] ?? 'root');
    $passEnv = getenv('DB_PASS');
    $pass = $passEnv !== false ? $passEnv : ($local['pass'] ?? '');
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    return $pdo;
}

<?php

$envFile = __DIR__ . '/../.env';
$environment = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : [];
$environment = is_array($environment) ? $environment : [];
$getSetting = static function (string $name, string $default = '') use ($environment): string {
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }
    return isset($environment[$name]) ? trim((string)$environment[$name], " \t\n\r\0\x0B\"'") : $default;
};

define('DB_DRIVER', strtolower($getSetting('DB_DRIVER', 'mysql')));
define('DB_HOST', $getSetting('DB_HOST', '127.0.0.1'));
define('DB_PORT', $getSetting('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
define('DB_USER', $getSetting('DB_USER', 'root'));
define('DB_PASS', $getSetting('DB_PASS'));
define('DB_NAME', $getSetting('DB_NAME', 'simpeg'));

// Atur Timezone Indonesia (WITA +08:00 / Asia/Makassar)
date_default_timezone_set('Asia/Makassar');

// MySQL remains available for local development; Supabase uses PostgreSQL.
if (DB_DRIVER === 'pgsql') {
    if (!extension_loaded('pdo_pgsql')) {
        http_response_code(500);
        die('Driver PDO PostgreSQL belum aktif. Aktifkan extension=pdo_pgsql di php.ini Laragon.');
    }

    try {
        $pdo = new PDO(
            'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->exec("SET TIME ZONE 'Asia/Makassar'");
        require_once __DIR__ . '/../src/database/LegacyMysqliCompat.php';
        $conn = new LegacyMysqliConnection($pdo);
    } catch (PDOException $exception) {
        http_response_code(500);
        error_log('Database connection failed: ' . $exception->getMessage());
        die('Koneksi database gagal. Periksa pengaturan DB di file .env.');
    }
} elseif (DB_DRIVER === 'mysql') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    if ($conn->connect_error) {
        http_response_code(500);
        die('Koneksi database gagal: ' . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
    }
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+08:00'");
} else {
    http_response_code(500);
    die('DB_DRIVER harus bernilai mysql atau pgsql.');
}

// Atur base URL — selalu mengarah ke root aplikasi (clean URL)
function get_base_url() {
    $port = $_SERVER['SERVER_PORT'] ?? 80;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $port == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Tentukan root aplikasi (folder simpeg)
    $script_dir = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

    // Cari posisi /simpeg/ lalu ambil sampai situ saja
    if (preg_match('#^(/[^/]+/simpeg)#', $script_dir, $m)) {
        $base_path = $m[1];
    } elseif (preg_match('#^(/simpeg)#', $script_dir, $m)) {
        $base_path = $m[1];
    } else {
        // Fallback: naik dari /public ke parent
        $base_path = rtrim(str_replace('/public', '', dirname($script_dir)), '/');
    }

    return $protocol . $host . $base_path . '/';
}

define('BASE_URL', get_base_url());

// Helper: buat URL halaman dengan clean path (tanpa ?page=)
function url(string $page): string {
    return BASE_URL . ltrim($page, '/');
}

// Keamanan Auth — Selalu aktifkan otentikasi wajib login
define('DISABLE_AUTH', false);
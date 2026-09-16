<?php

declare(strict_types=1);

/**
 * Titik inisialisasi aplikasi: autoload, konfigurasi, zona waktu, dan
 * penanganan galat. Berkas ini di-require oleh front controller dan
 * oleh semua skrip di folder bin/.
 */

use TokoKita\Config;
use TokoKita\Support\Logger;
use TokoKita\Support\Paths;

if (defined('TOKOKITA_BOOTSTRAPPED')) {
    return;
}

define('TOKOKITA_BOOTSTRAPPED', true);
define('TOKOKITA_START', microtime(true));

define('TOKOKITA_ROOT', dirname(__DIR__));
define('TOKOKITA_APP', TOKOKITA_ROOT . '/app');
define('TOKOKITA_STORAGE', TOKOKITA_ROOT . '/storage');
define('TOKOKITA_VIEWS', TOKOKITA_APP . '/Views');

require TOKOKITA_APP . '/Config.php';
require TOKOKITA_APP . '/Support/Paths.php';

Paths::setRoot(TOKOKITA_ROOT);

spl_autoload_register(static function (string $class): void {
    $prefix = 'TokoKita\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = TOKOKITA_APP . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

Config::load(TOKOKITA_ROOT . '/.env');

date_default_timezone_set(Config::string('APP_TIMEZONE', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'id_ID', 'Indonesian', 'C');

if (Config::bool('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

Logger::ensureDirectory();

set_exception_handler(static function (Throwable $e): void {
    Logger::error('Uncaught ' . $e::class . ': ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    if (Config::bool('APP_DEBUG', false)) {
        echo '<pre style="padding:24px;font:13px/1.6 ui-monospace,monospace;background:#1c1917;color:#fca5a5;white-space:pre-wrap">';
        echo htmlspecialchars($e::class . ': ' . $e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n";
        echo htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . "\n\n";
        echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        echo '</pre>';
    } else {
        echo '<h1>Terjadi kesalahan internal</h1><p>Silakan coba lagi beberapa saat lagi.</p>';
    }

    exit(1);
});

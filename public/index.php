<?php
//erewrwerwerwerweddasd
declare(strict_types=1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

try {
    define('BASE_PATH', dirname(__DIR__));

    require BASE_PATH . '/app/config/config.php';
    if (is_file(BASE_PATH . '/vendor/autoload.php')) {
        require BASE_PATH . '/vendor/autoload.php';
    }
    require BASE_PATH . '/app/core/Autoload.php';

    $app = new App\Core\App();
    $app->run();
} catch (\Throwable $e) {
    // Log complete error to server error log
    error_log('[FATAL ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    
    // Display friendly error page without leaking database secrets or file system paths
    http_response_code(500);
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'><title>System Error - Nutrify</title>";
    echo "<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;box-sizing:border-box;}";
    echo ".card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:32px;max-width:480px;text-align:center;box-shadow:0 10px 25px -5px rgba(0,0,0,0.5);}";
    echo "h1{color:#f87171;font-size:1.5rem;margin:0 0 12px;}p{color:#94a3b8;font-size:0.95rem;line-height:1.5;margin-bottom:24px;}";
    echo "a{display:inline-block;background:#3b82f6;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-weight:600;transition:background 0.2s;}a:hover{background:#2563eb;}</style></head>";
    echo "<body><div class='card'><h1>Something went wrong</h1><p>We encountered an unexpected error while processing your request. Please try again or return to the homepage.</p>";
    echo "<a href='index.php'>Go to Homepage</a></div></body></html>";
}

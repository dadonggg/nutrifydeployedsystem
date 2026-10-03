<?php

declare(strict_types=1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    define('BASE_PATH', dirname(__DIR__, 2));

    require BASE_PATH . '/app/config/config.php';
    if (is_file(BASE_PATH . '/vendor/autoload.php')) {
        require BASE_PATH . '/vendor/autoload.php';
    }
    require BASE_PATH . '/app/core/Autoload.php';

    // Forward Google OAuth callback into the existing router.
    $_GET['r'] = 'auth/googlecallback';

    $app = new App\Core\App();
    $app->run();
} catch (\Throwable $e) {
    echo "<div style='color:red; background:#ffe; padding:10px; border:2px solid red; font-family:sans-serif;'>";
    echo "<strong>FATAL ERROR:</strong> " . get_class($e) . "<br>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . " (line " . $e->getLine() . ")<br>";
    echo "<strong>Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}


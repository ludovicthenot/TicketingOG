<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

$configPath = BASE_PATH.'/config.php';
if (! is_file($configPath)) {
    http_response_code(500);
    exit('Configuration manquante. Copier config.example.php vers config.php.');
}

require_once $configPath;

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli') {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (PHP_SAPI !== 'cli' && ! headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require_once BASE_PATH.'/db.php';
require_once BASE_PATH.'/functions.php';
require_once BASE_PATH.'/queries.php';
require_once BASE_PATH.'/layout.php';

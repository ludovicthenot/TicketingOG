<?php

declare(strict_types=1);

require_once __DIR__.'/../app/bootstrap.php';

if (! is_post()) {
    http_response_code(405);
    exit('Methode non autorisee.');
}

csrf_check();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Strict',
    ]);
}

session_destroy();

redirect('/login.php');

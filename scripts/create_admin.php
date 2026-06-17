<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Ce script doit etre lance en CLI.\n");
}

require_once __DIR__.'/../bootstrap.php';

$options = getopt('', ['username:', 'email:', 'password:']);

$username = trim((string) ($options['username'] ?? ''));
$email = filter_var(trim((string) ($options['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$password = (string) ($options['password'] ?? '');

if ($username === '' || strlen($username) > 100 || ! $email || strlen($password) < 8) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php --username=admin --email=admin@example.com --password=Password123!\n");
    exit(1);
}

$stmt = db()->prepare(
    'INSERT INTO users (username, email, password_hash, role)
     VALUES (:username, :email, :password_hash, :role)'
);
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
    'role' => 'admin',
]);

echo "Admin cree: {$email}\n";

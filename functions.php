<?php

declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: '.$path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'.h(csrf_token()).'">';
}

function csrf_check(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';

    if (! is_string($postedToken) || ! hash_equals($sessionToken, $postedToken)) {
        http_response_code(403);
        exit('Token CSRF invalide.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (! empty($_SESSION['current_user']) && (int) $_SESSION['current_user']['id'] === (int) $_SESSION['user_id']) {
        return $_SESSION['current_user'];
    }

    $stmt = db()->prepare(
        'SELECT id, username, email, role, created_at, updated_at
         FROM users
         WHERE id = :id'
    );
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (! $user) {
        $_SESSION = [];
        return null;
    }

    $_SESSION['current_user'] = $user;

    return $user;
}

function require_auth(): array
{
    $user = current_user();

    if (! $user) {
        redirect('/login.php');
    }

    return $user;
}

function require_guest(): void
{
    if (current_user()) {
        redirect('/dashboard.php');
    }
}

function field_value(string $name, mixed $default = ''): string
{
    return h($_POST[$name] ?? $default);
}

function priorities(): array
{
    return [
        'low' => 'Basse',
        'medium' => 'Normale',
        'high' => 'Haute',
        'urgent' => 'Urgente',
    ];
}

function ticket_statuses(): array
{
    return [
        'open' => 'Ouvert',
        'in_progress' => 'En cours',
        'waiting' => 'En attente',
        'resolved' => 'Resolu',
        'closed' => 'Ferme',
    ];
}

function project_statuses(): array
{
    return [
        'active' => 'Actif',
        'archived' => 'Archive',
        'closed' => 'Ferme',
    ];
}

function roles(): array
{
    return [
        'admin' => 'Admin',
        'technician' => 'Technicien',
        'client' => 'Client',
    ];
}

function role_is(array $user, string $role): bool
{
    return ($user['role'] ?? '') === $role;
}

function user_is_staff(array $user): bool
{
    return role_is($user, 'admin') || role_is($user, 'technician');
}

function require_roles(array $user, array $roles): void
{
    if (! in_array($user['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Acces refuse.');
    }
}

function validation_error(array &$errors, string $message): void
{
    $errors[] = $message;
}

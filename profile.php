<?php

declare(strict_types=1);

require_once __DIR__.'/auth.php';

$errors = [];

if (is_post()) {
    csrf_check();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);

        if (strlen($username) < 2 || strlen($username) > 100) {
            validation_error($errors, 'Nom utilisateur : 2 a 100 caracteres.');
        }

        if (! $email) {
            validation_error($errors, 'Email invalide.');
        }

        if ($errors === []) {
            $check = db()->prepare(
                'SELECT id
                 FROM users
                 WHERE email = :email
                 AND id != :id
                 LIMIT 1'
            );
            $check->execute([
                'email' => $email,
                'id' => (int) $user['id'],
            ]);

            if ($check->fetch()) {
                validation_error($errors, 'Email deja utilise.');
            }
        }

        if ($errors === []) {
            $stmt = db()->prepare(
                'UPDATE users
                 SET username = :username, email = :email
                 WHERE id = :id'
            );
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'id' => (int) $user['id'],
            ]);

            unset($_SESSION['current_user']);
            flash('success', 'Profil mis a jour.');
            redirect('/profile.php');
        }
    } elseif ($action === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if (strlen($newPassword) < 8) {
            validation_error($errors, 'Nouveau mot de passe : minimum 8 caracteres.');
        }

        if ($newPassword !== $passwordConfirm) {
            validation_error($errors, 'La confirmation ne correspond pas.');
        }

        if ($errors === []) {
            $stmt = db()->prepare(
                'SELECT password_hash
                 FROM users
                 WHERE id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => (int) $user['id']]);
            $account = $stmt->fetch();

            if (! $account || ! password_verify($currentPassword, $account['password_hash'])) {
                validation_error($errors, 'Mot de passe actuel incorrect.');
            }
        }

        if ($errors === []) {
            $stmt = db()->prepare(
                'UPDATE users
                 SET password_hash = :password_hash
                 WHERE id = :id'
            );
            $stmt->execute([
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                'id' => (int) $user['id'],
            ]);

            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            flash('success', 'Mot de passe mis a jour.');
            redirect('/profile.php');
        }
    } else {
        http_response_code(400);
        exit('Action invalide.');
    }
}

$user = require_auth();

render_header('Profil', $user);
render_errors($errors);
?>
<div class="actions">
    <h1>Profil</h1>
</div>

<section class="grid">
    <div class="panel">
        <h2>Informations</h2>
        <form method="POST" action="/profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">

            <div class="field">
                <label for="username">Nom utilisateur</label>
                <input id="username" name="username" value="<?= field_value('username', $user['username']) ?>" required minlength="2" maxlength="100">
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= field_value('email', $user['email']) ?>" required>
            </div>

            <button class="button" type="submit">Enregistrer</button>
        </form>
    </div>

    <div class="panel">
        <h2>Mot de passe</h2>
        <form method="POST" action="/profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <div class="field">
                <label for="current_password">Mot de passe actuel</label>
                <input id="current_password" name="current_password" type="password" required>
            </div>

            <div class="field">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" name="password" type="password" required minlength="8">
            </div>

            <div class="field">
                <label for="password_confirm">Confirmation</label>
                <input id="password_confirm" name="password_confirm" type="password" required minlength="8">
            </div>

            <button class="button" type="submit">Changer</button>
        </form>
    </div>
</section>
<?php render_footer(); ?>

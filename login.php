<?php

declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';

require_guest();

$errors = [];

if (isset($_SESSION['last_login_attempt']) && time() - (int) $_SESSION['last_login_attempt'] > 300) {
    unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
}

if (is_post()) {
    csrf_check();

    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = trim((string) ($_POST['password'] ?? ''));

    if (! $email) {
        validation_error($errors, 'Email invalide.');
    }

    if (strlen($password) < 8) {
        validation_error($errors, 'Mot de passe trop court.');
    }

    if (($_SESSION['login_attempts'] ?? 0) >= 5) {
        http_response_code(429);
        validation_error($errors, 'Trop de tentatives. Reessaie dans quelques minutes.');
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'SELECT id, username, email, password_hash, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $account = $stmt->fetch();

        if (! $account || ! password_verify($password, $account['password_hash'])) {
            $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_login_attempt'] = time();
            sleep(1);
            validation_error($errors, 'Identifiants incorrects.');
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['id'];
            $_SESSION['user_email'] = $account['email'];
            $_SESSION['user_role'] = $account['role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt'], $_SESSION['current_user']);

            $log = db()->prepare(
                'INSERT INTO login_logs (user_id, ip_address, user_agent)
                 VALUES (:user_id, :ip_address, :user_agent)'
            );
            $log->execute([
                'user_id' => (int) $account['id'],
                'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]);

            redirect('/dashboard.php');
        }
    }
}

render_header('Connexion');
render_errors($errors);
?>
<section class="panel login-box">
    <h1>Connexion</h1>
    <form method="POST" action="/login.php">
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= field_value('email') ?>" required>
        </div>

        <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required minlength="8">
        </div>

        <button class="button" type="submit">Se connecter</button>
    </form>
</section>
<?php render_footer(); ?>

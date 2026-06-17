<?php

declare(strict_types=1);

require_once __DIR__.'/../app/auth.php';

require_roles($user, ['admin']);

$errors = [];

if (is_post()) {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'client');

    if (strlen($username) < 2 || strlen($username) > 100) {
        validation_error($errors, 'Nom utilisateur : 2 a 100 caracteres.');
    }

    if (! $email) {
        validation_error($errors, 'Email invalide.');
    }

    if (strlen($password) < 8) {
        validation_error($errors, 'Mot de passe : minimum 8 caracteres.');
    }

    if (! array_key_exists($role, roles())) {
        validation_error($errors, 'Role invalide.');
    }

    if ($errors === []) {
        $check = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            validation_error($errors, 'Email deja utilise.');
        }
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO users (username, email, password_hash, role)
             VALUES (:username, :email, :password_hash, :role)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
        ]);

        flash('success', 'Utilisateur cree.');
        redirect('/users.php');
    }
}

$stmt = db()->prepare(
    'SELECT id, username, email, role, created_at
     FROM users
     ORDER BY created_at DESC'
);
$stmt->execute();
$users = $stmt->fetchAll();

render_header('Utilisateurs', $user);
render_errors($errors);
?>
<div class="actions">
    <h1>Utilisateurs</h1>
</div>

<section class="grid">
    <div class="panel">
        <h2>Nouvel utilisateur</h2>
        <form method="POST" action="/users.php">
            <?= csrf_field() ?>

            <div class="field">
                <label for="username">Nom utilisateur</label>
                <input id="username" name="username" value="<?= field_value('username') ?>" required minlength="2" maxlength="100">
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= field_value('email') ?>" required>
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required minlength="8">
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <?php foreach (roles() as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= ($_POST['role'] ?? 'client') === $value ? 'selected' : '' ?>>
                            <?= h($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="button" type="submit">Creer</button>
        </form>
    </div>

    <div class="panel">
        <h2>Liste</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td><?= h($account['username']) ?></td>
                        <td><?= h($account['email']) ?></td>
                        <td><span class="badge"><?= h(roles()[$account['role']] ?? $account['role']) ?></span></td>
                        <td><?= h($account['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($users === []): ?>
                    <tr>
                        <td colspan="4" class="muted">Aucun utilisateur.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>

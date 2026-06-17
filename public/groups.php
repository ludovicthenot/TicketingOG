<?php

declare(strict_types=1);

require_once __DIR__.'/../app/auth.php';

require_roles($user, ['admin', 'technician']);

$errors = [];

if (is_post()) {
    csrf_check();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'active');

        if (strlen($name) < 2 || strlen($name) > 100) {
            validation_error($errors, 'Nom du groupe : 2 a 100 caracteres.');
        }

        if (strlen($description) > 5000) {
            validation_error($errors, 'Description : maximum 5000 caracteres.');
        }

        if (! in_array($status, ['active', 'inactive'], true)) {
            validation_error($errors, 'Statut de groupe invalide.');
        }

        if ($errors === []) {
            $stmt = db()->prepare(
                'INSERT INTO `groups` (name, description, status)
                 VALUES (:name, :description, :status)'
            );
            $stmt->execute([
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'status' => $status,
            ]);

            flash('success', 'Groupe cree.');
            redirect('/groups.php');
        }
    } elseif ($action === 'member') {
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($groupId <= 0 || $userId <= 0) {
            validation_error($errors, 'Groupe ou utilisateur invalide.');
        }

        if ($errors === []) {
            $stmt = db()->prepare(
                'INSERT INTO group_members (group_id, user_id)
                 VALUES (:group_id, :user_id)
                 ON DUPLICATE KEY UPDATE joined_at = joined_at'
            );
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId,
            ]);

            flash('success', 'Membre ajoute au groupe.');
            redirect('/groups.php');
        }
    } else {
        http_response_code(400);
        exit('Action invalide.');
    }
}

$groupsStmt = db()->prepare(
    'SELECT
        g.id,
        g.name,
        g.description,
        g.status,
        g.updated_at,
        (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS members_count
     FROM `groups` g
     ORDER BY g.updated_at DESC'
);
$groupsStmt->execute();
$groups = $groupsStmt->fetchAll();

$usersStmt = db()->prepare(
    "SELECT id, username, email
     FROM users
     WHERE role IN ('technician', 'client')
     ORDER BY username"
);
$usersStmt->execute();
$availableUsers = $usersStmt->fetchAll();

render_header('Groupes', $user);
render_errors($errors);
?>
<div class="actions">
    <h1>Groupes</h1>
</div>

<section class="grid">
    <div class="panel">
        <h2>Nouveau groupe</h2>
        <form method="POST" action="/groups.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <div class="field">
                <label for="name">Nom</label>
                <input id="name" name="name" value="<?= field_value('name') ?>" required minlength="2" maxlength="100">
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="5000"><?= field_value('description') ?></textarea>
            </div>

            <div class="field">
                <label for="status">Statut</label>
                <select id="status" name="status">
                    <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>

            <button class="button" type="submit">Creer</button>
        </form>
    </div>

    <div class="panel">
        <h2>Ajouter un membre</h2>
        <form method="POST" action="/groups.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="member">

            <div class="field">
                <label for="group_id">Groupe</label>
                <select id="group_id" name="group_id" required>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= (int) $group['id'] ?>"><?= h($group['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="user_id">Utilisateur</label>
                <select id="user_id" name="user_id" required>
                    <?php foreach ($availableUsers as $account): ?>
                        <option value="<?= (int) $account['id'] ?>"><?= h($account['username']) ?> - <?= h($account['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="button" type="submit">Ajouter</button>
        </form>
    </div>
</section>

<section class="panel" style="margin-top: 20px;">
    <h2>Liste des groupes</h2>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Statut</th>
                <th>Membres</th>
                <th>Mis a jour</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groups as $group): ?>
                <tr>
                    <td><?= h($group['name']) ?></td>
                    <td><span class="badge"><?= h($group['status']) ?></span></td>
                    <td><?= (int) $group['members_count'] ?></td>
                    <td><?= h($group['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($groups === []): ?>
                <tr>
                    <td colspan="4" class="muted">Aucun groupe.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>

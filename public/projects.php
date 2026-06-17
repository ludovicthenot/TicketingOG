<?php

declare(strict_types=1);

require_once __DIR__.'/../app/auth.php';

$errors = [];

if (is_post()) {
    csrf_check();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'active');

    if (strlen($title) < 3 || strlen($title) > 255) {
        validation_error($errors, 'Titre : 3 a 255 caracteres.');
    }

    if (strlen($description) > 5000) {
        validation_error($errors, 'Description : maximum 5000 caracteres.');
    }

    if (! array_key_exists($status, project_statuses())) {
        validation_error($errors, 'Statut projet invalide.');
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO projects (title, description, status, created_by)
             VALUES (:title, :description, :status, :created_by)'
        );
        $stmt->execute([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'status' => $status,
            'created_by' => (int) $user['id'],
        ]);

        flash('success', 'Projet cree.');
        redirect('/projects.php');
    }
}

[$visibility, $params] = project_visibility_sql($user, 'p', 'projects');

$stmt = db()->prepare(
    "SELECT
        p.id,
        p.title,
        p.description,
        p.status,
        p.updated_at,
        u.username AS creator_username,
        (SELECT COUNT(*) FROM tickets t WHERE t.project_id = p.id) AS tickets_count
     FROM projects p
     INNER JOIN users u ON u.id = p.created_by
     WHERE {$visibility}
     ORDER BY p.updated_at DESC"
);
$stmt->execute($params);
$projects = $stmt->fetchAll();

render_header('Projets', $user);
render_errors($errors);
?>
<div class="actions">
    <h1>Projets</h1>
</div>

<section class="grid">
    <div class="panel">
        <h2>Nouveau projet</h2>
        <form method="POST" action="/projects.php">
            <?= csrf_field() ?>

            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="<?= field_value('title') ?>" required minlength="3" maxlength="255">
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="5000"><?= field_value('description') ?></textarea>
            </div>

            <div class="field">
                <label for="status">Statut</label>
                <select id="status" name="status">
                    <?php foreach (project_statuses() as $value => $label): ?>
                        <option value="<?= h($value) ?>" <?= ($_POST['status'] ?? 'active') === $value ? 'selected' : '' ?>>
                            <?= h($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="button" type="submit">Creer</button>
        </form>
    </div>

    <div class="panel">
        <h2>Liste des projets</h2>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Statut</th>
                    <th>Cree par</th>
                    <th>Tickets</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td><?= h($project['title']) ?></td>
                        <td><span class="badge"><?= h(project_statuses()[$project['status']] ?? $project['status']) ?></span></td>
                        <td><?= h($project['creator_username']) ?></td>
                        <td><?= (int) $project['tickets_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($projects === []): ?>
                    <tr>
                        <td colspan="4" class="muted">Aucun projet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>

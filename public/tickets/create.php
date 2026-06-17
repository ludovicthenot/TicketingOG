<?php

declare(strict_types=1);

require_once __DIR__.'/../../app/auth.php';

$errors = [];
[$projectVisibility, $projectParams] = project_visibility_sql($user, 'p', 'ticket_create_project');

if (is_post()) {
    csrf_check();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $priority = (string) ($_POST['priority'] ?? 'medium');
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $deadlineRaw = trim((string) ($_POST['deadline'] ?? ''));
    $deadline = null;

    if (strlen($title) < 3 || strlen($title) > 255) {
        validation_error($errors, 'Titre : 3 a 255 caracteres.');
    }

    if (strlen($description) < 10 || strlen($description) > 5000) {
        validation_error($errors, 'Description : 10 a 5000 caracteres.');
    }

    if (! array_key_exists($priority, priorities())) {
        validation_error($errors, 'Priorite invalide.');
    }

    if ($projectId <= 0) {
        validation_error($errors, 'Projet invalide.');
    }

    if ($deadlineRaw !== '') {
        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $deadlineRaw);

        if (! $date) {
            validation_error($errors, 'Deadline invalide.');
        } else {
            $deadline = $date->format('Y-m-d H:i:s');
        }
    }

    if ($errors === []) {
        $project = fetch_visible_project($projectId, $user);

        if (! $project || $project['status'] !== 'active') {
            http_response_code(403);
            exit('Acces refuse.');
        }

        $stmt = db()->prepare(
            'INSERT INTO tickets (project_id, created_by, title, description, priority, deadline)
             VALUES (:project_id, :created_by, :title, :description, :priority, :deadline)'
        );
        $stmt->execute([
            'project_id' => $projectId,
            'created_by' => (int) $user['id'],
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'deadline' => $deadline,
        ]);

        flash('success', 'Ticket cree.');
        redirect('/tickets/view.php?id='.(int) db()->lastInsertId());
    }
}

$projectsStmt = db()->prepare(
    "SELECT p.id, p.title
     FROM projects p
     WHERE p.status = 'active'
     AND {$projectVisibility}
     ORDER BY p.title"
);
$projectsStmt->execute($projectParams);
$projects = $projectsStmt->fetchAll();

render_header('Nouveau ticket', $user);
render_errors($errors);
?>
<div class="actions">
    <h1>Nouveau ticket</h1>
    <a class="button secondary" href="/tickets/list.php">Retour</a>
</div>

<section class="panel">
    <form method="POST" action="/tickets/create.php">
        <?= csrf_field() ?>

        <div class="field">
            <label for="project_id">Projet</label>
            <select id="project_id" name="project_id" required>
                <option value="">Choisir un projet</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= (int) $project['id'] ?>" <?= (int) ($_POST['project_id'] ?? 0) === (int) $project['id'] ? 'selected' : '' ?>>
                        <?= h($project['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="title">Titre</label>
            <input id="title" name="title" value="<?= field_value('title') ?>" required minlength="3" maxlength="255">
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" required minlength="10" maxlength="5000"><?= field_value('description') ?></textarea>
        </div>

        <div class="field">
            <label for="priority">Priorite</label>
            <select id="priority" name="priority" required>
                <?php foreach (priorities() as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= ($_POST['priority'] ?? 'medium') === $value ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="deadline">Deadline</label>
            <input id="deadline" name="deadline" type="datetime-local" value="<?= field_value('deadline') ?>">
        </div>

        <button class="button" type="submit">Creer le ticket</button>
    </form>
</section>
<?php render_footer(); ?>

<?php

declare(strict_types=1);

require_once __DIR__.'/../../app/auth.php';

$status = (string) ($_GET['status'] ?? '');
$priority = (string) ($_GET['priority'] ?? '');
$filters = [];
$filterParams = [];

[$visibility, $params] = ticket_visibility_sql($user, 't', 'tickets_list');

if ($status !== '') {
    if (! array_key_exists($status, ticket_statuses())) {
        http_response_code(400);
        exit('Statut invalide.');
    }

    $filters[] = 't.status = :status';
    $filterParams['status'] = $status;
}

if ($priority !== '') {
    if (! array_key_exists($priority, priorities())) {
        http_response_code(400);
        exit('Priorite invalide.');
    }

    $filters[] = 't.priority = :priority';
    $filterParams['priority'] = $priority;
}

$where = $visibility;
if ($filters !== []) {
    $where .= ' AND '.implode(' AND ', $filters);
}

$stmt = db()->prepare(
    "SELECT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.deadline,
        t.updated_at,
        p.title AS project_title,
        u.username AS creator_username,
        (SELECT COUNT(*) FROM ticket_comments tc WHERE tc.ticket_id = t.id) AS comments_count
     FROM tickets t
     INNER JOIN projects p ON p.id = t.project_id
     INNER JOIN users u ON u.id = t.created_by
     WHERE {$where}
     ORDER BY t.updated_at DESC"
);
$stmt->execute(array_merge($params, $filterParams));
$tickets = $stmt->fetchAll();

render_header('Tickets', $user);
?>
<div class="actions">
    <h1>Tickets</h1>
    <a class="button" href="/tickets/create.php">Nouveau ticket</a>
</div>

<section class="panel">
    <form method="GET" action="/tickets/list.php" class="filters">
        <div>
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous</option>
                <?php foreach (ticket_statuses() as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="priority">Priorite</label>
            <select id="priority" name="priority">
                <option value="">Toutes</option>
                <?php foreach (priorities() as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $priority === $value ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="button secondary" type="submit">Filtrer</button>
    </form>
</section>

<section class="panel">
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Projet</th>
                <th>Statut</th>
                <th>Priorite</th>
                <th>Commentaires</th>
                <th>Deadline</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><a href="/tickets/view.php?id=<?= (int) $ticket['id'] ?>"><?= h($ticket['title']) ?></a></td>
                    <td><?= h($ticket['project_title']) ?></td>
                    <td><span class="badge"><?= h(ticket_statuses()[$ticket['status']] ?? $ticket['status']) ?></span></td>
                    <td><span class="badge"><?= h(priorities()[$ticket['priority']] ?? $ticket['priority']) ?></span></td>
                    <td><?= (int) $ticket['comments_count'] ?></td>
                    <td><?= h($ticket['deadline'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($tickets === []): ?>
                <tr>
                    <td colspan="6" class="muted">Aucun ticket.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>

<?php

declare(strict_types=1);

require_once __DIR__.'/../app/auth.php';

[$visibility, $params] = ticket_visibility_sql($user, 't', 'dash');

$statsStmt = db()->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN t.status = 'open' THEN 1 ELSE 0 END), 0) AS open_count,
        COALESCE(SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END), 0) AS in_progress_count,
        COALESCE(SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END), 0) AS resolved_count,
        COALESCE(SUM(CASE WHEN t.priority = 'urgent' THEN 1 ELSE 0 END), 0) AS urgent_count
     FROM tickets t
     WHERE {$visibility}"
);
$statsStmt->execute($params);
$stats = $statsStmt->fetch() ?: [
    'open_count' => 0,
    'in_progress_count' => 0,
    'resolved_count' => 0,
    'urgent_count' => 0,
];

$recentStmt = db()->prepare(
    "SELECT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.updated_at,
        p.title AS project_title,
        (SELECT COUNT(*) FROM ticket_comments tc WHERE tc.ticket_id = t.id) AS comments_count
     FROM tickets t
     INNER JOIN projects p ON p.id = t.project_id
     WHERE {$visibility}
     ORDER BY t.updated_at DESC
     LIMIT 8"
);
$recentStmt->execute($params);
$recentTickets = $recentStmt->fetchAll();

render_header('Dashboard', $user);
?>
<div class="actions">
    <h1>Dashboard</h1>
    <a class="button" href="/tickets/create.php">Nouveau ticket</a>
</div>

<section class="grid">
    <div class="panel">
        <div class="muted">Ouverts</div>
        <div class="stat"><?= (int) $stats['open_count'] ?></div>
    </div>
    <div class="panel">
        <div class="muted">En cours</div>
        <div class="stat"><?= (int) $stats['in_progress_count'] ?></div>
    </div>
    <div class="panel">
        <div class="muted">Resolus</div>
        <div class="stat"><?= (int) $stats['resolved_count'] ?></div>
    </div>
    <div class="panel">
        <div class="muted">Urgents</div>
        <div class="stat"><?= (int) $stats['urgent_count'] ?></div>
    </div>
</section>

<section class="panel" style="margin-top: 24px;">
    <h2>Tickets recents</h2>
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Projet</th>
                <th>Statut</th>
                <th>Priorite</th>
                <th>Commentaires</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentTickets as $ticket): ?>
                <tr>
                    <td><a href="/tickets/view.php?id=<?= (int) $ticket['id'] ?>"><?= h($ticket['title']) ?></a></td>
                    <td><?= h($ticket['project_title']) ?></td>
                    <td><span class="badge"><?= h(ticket_statuses()[$ticket['status']] ?? $ticket['status']) ?></span></td>
                    <td><span class="badge"><?= h(priorities()[$ticket['priority']] ?? $ticket['priority']) ?></span></td>
                    <td><?= (int) $ticket['comments_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($recentTickets === []): ?>
                <tr>
                    <td colspan="5" class="muted">Aucun ticket.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>

<?php

declare(strict_types=1);

require_once __DIR__.'/../../app/auth.php';

$ticketId = (int) ($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    http_response_code(404);
    exit('Ticket introuvable.');
}

$ticket = fetch_visible_ticket($ticketId, $user);
if (! $ticket) {
    http_response_code(403);
    exit('Acces refuse.');
}

$errors = [];

if (is_post()) {
    csrf_check();

    $content = trim((string) ($_POST['content'] ?? ''));

    if (strlen($content) < 2 || strlen($content) > 5000) {
        validation_error($errors, 'Commentaire : 2 a 5000 caracteres.');
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO ticket_comments (ticket_id, user_id, content)
             VALUES (:ticket_id, :user_id, :content)'
        );
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => (int) $user['id'],
            'content' => $content,
        ]);

        flash('success', 'Commentaire ajoute.');
        redirect('/tickets/view.php?id='.$ticketId);
    }
}

$commentsStmt = db()->prepare(
    'SELECT tc.content, tc.created_at, u.username
     FROM ticket_comments tc
     INNER JOIN users u ON u.id = tc.user_id
     WHERE tc.ticket_id = :ticket_id
     ORDER BY tc.created_at ASC'
);
$commentsStmt->execute(['ticket_id' => $ticketId]);
$comments = $commentsStmt->fetchAll();

render_header($ticket['title'], $user);
render_errors($errors);
?>
<div class="actions">
    <h1><?= h($ticket['title']) ?></h1>
    <a class="button secondary" href="/tickets/list.php">Retour</a>
</div>

<section class="grid">
    <div class="panel">
        <h2>Details</h2>
        <p><strong>Projet :</strong> <?= h($ticket['project_title']) ?></p>
        <p><strong>Cree par :</strong> <?= h($ticket['creator_username']) ?></p>
        <p><strong>Statut :</strong> <span class="badge"><?= h(ticket_statuses()[$ticket['status']] ?? $ticket['status']) ?></span></p>
        <p><strong>Priorite :</strong> <span class="badge"><?= h(priorities()[$ticket['priority']] ?? $ticket['priority']) ?></span></p>
        <p><strong>Deadline :</strong> <?= h($ticket['deadline'] ?? 'Non definie') ?></p>
        <p><strong>Ferme le :</strong> <?= h($ticket['closed_at'] ?? 'Non ferme') ?></p>
    </div>

    <div class="panel">
        <h2>Description</h2>
        <p><?= nl2br(h($ticket['description'] ?? '')) ?></p>
    </div>
</section>

<section class="panel">
    <h2>Commentaires</h2>
    <div class="stack">
        <?php foreach ($comments as $comment): ?>
            <article>
                <p class="muted"><?= h($comment['username']) ?> - <?= h($comment['created_at']) ?></p>
                <p><?= nl2br(h($comment['content'])) ?></p>
            </article>
        <?php endforeach; ?>

        <?php if ($comments === []): ?>
            <p class="muted">Aucun commentaire.</p>
        <?php endif; ?>
    </div>

    <form method="POST" action="/tickets/view.php?id=<?= (int) $ticket['id'] ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="content">Ajouter un commentaire</label>
            <textarea id="content" name="content" required minlength="2" maxlength="5000"><?= field_value('content') ?></textarea>
        </div>
        <button class="button" type="submit">Ajouter</button>
    </form>
</section>
<?php render_footer(); ?>

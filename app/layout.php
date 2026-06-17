<?php

declare(strict_types=1);

function render_header(string $title, ?array $user = null): void
{
    $flashes = consume_flash();
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> - <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <a class="brand" href="/dashboard.php">TicketingOG</a>
            <?php if ($user): ?>
                <nav class="nav">
                    <a href="/dashboard.php">Dashboard</a>
                    <a href="/tickets/list.php">Tickets</a>
                    <a href="/projects.php">Projets</a>
                    <?php if (role_is($user, 'admin')): ?>
                        <a href="/users.php">Utilisateurs</a>
                    <?php endif; ?>
                    <?php if (user_is_staff($user)): ?>
                        <a href="/groups.php">Groupes</a>
                    <?php endif; ?>
                    <a href="/profile.php">Profil</a>
                    <form method="POST" action="/logout.php" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button">Deconnexion</button>
                    </form>
                </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container page">
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash <?= h($type) ?>"><?= h($message) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php
}

function render_errors(array $errors): void
{
    if ($errors === []) {
        return;
    }
    ?>
    <div class="flash error">
        <strong>Erreur de validation</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}

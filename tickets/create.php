<?php
require_once '../bootstrap.php';


$user = require_auth();


if (is_post()) {
    csrf_check();
    $titre = trim($_POST["titre"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $priorite = $_POST["priorite"] ?? "";
    $projet_id = (int) ($_POST["projet_id"] ?? 0);

    // Whitelist — seules valeurs autorisées pour la priorité
    if (!in_array($priorite, array_keys(priorities()), true)) {
        die('Priorité invalide');

    }
    // Longueurs
    if (strlen($titre) < 3 || strlen($titre) > 255)
        die('Titre : 3 à 255 caractères');
    if (strlen($description) < 10 || strlen($description) > 5000)
        die('Description : 10 à 5000 caractères');

    if ($projet_id <= 0) {
        die('Projet invalide');
    }
    $stmt = $pdo->prepare("INSERT INTO tickets (title, project_id, description, created_by, priority) 
VALUES (:title, :project_id, :description, :created_by, :priority)");

    $stmt->execute([
        ':title' => $titre,
        ':project_id' => $projet_id,
        ':description' => $description,
        ':created_by' => $user['id'],
        ':priority' => $priorite
    ]);

    $ticket_id = $pdo->lastInsertId() or die('Aucun ticket créé');

    redirect('view.php?id=' . $ticket_id);
}



?>
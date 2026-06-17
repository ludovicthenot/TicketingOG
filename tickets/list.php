<?php

require_once '../bootstrap.php';

$user = require_auth();





[$visibility, $params] = ticket_visibility_sql($user);
$stmt = $pdo->prepare(
    "SELECT t.*, p.title AS project_title, u.username AS creator_username
     FROM tickets t
     INNER JOIN projects p ON p.id = t.project_id
     INNER JOIN users u ON u.id = t.created_by
     WHERE {$visibility}
     ORDER BY t.created_at DESC"
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();


?>
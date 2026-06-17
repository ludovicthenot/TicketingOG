<?php

require_once '../bootstrap.php';

$user = require_auth();

$ticket_id = (int) ($_GET['id'] ?? 0);

$ticket = fetch_visible_ticket($ticket_id, $user);

if (!$ticket) {
    http_response_code(404);
    die('Ticket introuvable.');
}
?>
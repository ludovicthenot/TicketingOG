<?php

declare(strict_types=1);

function ticket_visibility_sql(array $user, string $alias = 't', string $prefix = 'tv'): array
{
    if (role_is($user, 'admin')) {
        return ['1 = 1', []];
    }

    $id = (int) $user['id'];

    return [
        "(
            {$alias}.created_by = :{$prefix}_owner
            OR EXISTS (
                SELECT 1
                FROM ticket_assignments ta
                WHERE ta.ticket_id = {$alias}.id
                AND ta.user_id = :{$prefix}_direct
            )
            OR EXISTS (
                SELECT 1
                FROM ticket_group_assignments tga
                INNER JOIN group_members gm ON gm.group_id = tga.group_id
                WHERE tga.ticket_id = {$alias}.id
                AND gm.user_id = :{$prefix}_group
            )
        )",
        [
            "{$prefix}_owner" => $id,
            "{$prefix}_direct" => $id,
            "{$prefix}_group" => $id,
        ],
    ];
}

function project_visibility_sql(array $user, string $alias = 'p', string $prefix = 'pv'): array
{
    if (role_is($user, 'admin') || role_is($user, 'technician')) {
        return ['1 = 1', []];
    }

    return [
        "{$alias}.created_by = :{$prefix}_owner",
        ["{$prefix}_owner" => (int) $user['id']],
    ];
}

function fetch_visible_ticket(int $ticketId, array $user): ?array
{
    [$visibility, $params] = ticket_visibility_sql($user, 't', 'ticket_fetch');
    $params['ticket_id'] = $ticketId;

    $stmt = db()->prepare(
        "SELECT t.*, p.title AS project_title, u.username AS creator_username
         FROM tickets t
         INNER JOIN projects p ON p.id = t.project_id
         INNER JOIN users u ON u.id = t.created_by
         WHERE t.id = :ticket_id
         AND {$visibility}
         LIMIT 1"
    );
    $stmt->execute($params);
    $ticket = $stmt->fetch();

    return $ticket ?: null;
}

function fetch_visible_project(int $projectId, array $user): ?array
{
    [$visibility, $params] = project_visibility_sql($user, 'p', 'project_fetch');
    $params['project_id'] = $projectId;

    $stmt = db()->prepare(
        "SELECT p.*, u.username AS creator_username
         FROM projects p
         INNER JOIN users u ON u.id = p.created_by
         WHERE p.id = :project_id
         AND {$visibility}
         LIMIT 1"
    );
    $stmt->execute($params);
    $project = $stmt->fetch();

    return $project ?: null;
}

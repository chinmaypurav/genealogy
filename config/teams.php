<?php

declare(strict_types=1);

/*
 * Team roles, keyed by the value stored in team_user.role and team_invitations.role.
 * Team owners get every permission, whatever role they hold.
 */
return [
    'roles' => [
        'administrator' => [
            'name'        => 'Administrator',
            'description' => 'Administrators can perform any action and manage the application.',
            'permissions' => [
                'user:create', 'user:read', 'user:update', 'user:delete',
                'person:create', 'person:read', 'person:update', 'person:delete',
                'couple:create', 'couple:read', 'couple:update', 'couple:delete',
            ],
        ],
        'manager' => [
            'name'        => 'Manager',
            'description' => 'Managers can perform any action on people.',
            'permissions' => [
                'person:create', 'person:read', 'person:update', 'person:delete',
                'couple:create', 'couple:read', 'couple:update', 'couple:delete',
            ],
        ],
        'editor' => [
            'name'        => 'Editor',
            'description' => 'Editors have the ability to create, read and update people.',
            'permissions' => [
                'person:create', 'person:read', 'person:update',
                'couple:create', 'couple:read', 'couple:update',
            ],
        ],
        'member' => [
            'name'        => 'Member',
            'description' => 'Members have the ability to read people.',
            'permissions' => [
                'person:read',
                'couple:read',
            ],
        ],
    ],
];

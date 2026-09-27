<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A team member's role: its key, display name and the permissions it grants.
 *
 * Replaces Jetstream's Role and OwnerRole so the teams layer no longer depends on the package.
 * Roles are defined in config/teams.php; the owner role is implicit and grants every permission.
 */
class TeamRole
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $key,
        public string $name,
        public array $permissions,
        public ?string $description = null,
    ) {}

    public static function owner(): self
    {
        return new self('owner', 'Owner', ['*']);
    }

    public static function find(string $key): ?self
    {
        /** @var array{name: string, description: string, permissions: list<string>}|null $role */
        $role = config('teams.roles')[$key] ?? null;

        if ($role === null) {
            return null;
        }

        return new self($key, $role['name'], $role['permissions'], $role['description']);
    }
}

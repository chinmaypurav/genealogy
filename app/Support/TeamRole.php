<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A team member's role: its key, display name and the permissions it grants.
 *
 * A small value object so views and permission checks read roles the same way.
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

    /**
     * Every assignable role, in the order defined in config/teams.php.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return array_values(array_filter(array_map(self::find(...), array_keys(self::definitions()))));
    }

    public static function find(string $key): ?self
    {
        $role = self::definitions()[$key] ?? null;

        if ($role === null) {
            return null;
        }

        return new self($key, $role['name'], $role['permissions'], $role['description']);
    }

    /**
     * @return array<string, array{name: string, description: string, permissions: list<string>}>
     */
    protected static function definitions(): array
    {
        return config('teams.roles');
    }
}

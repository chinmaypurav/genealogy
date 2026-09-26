<?php

declare(strict_types=1);

namespace App\Queries;

use App\Contracts\AncestorsQueryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PgSqlAncestorsQuery implements AncestorsQueryInterface
{
    /**
     * Get ancestors for a person up to a maximum depth.
     *
     * @return Collection<int, object{
     *     id: int,
     *     firstname: string|null,
     *     surname: string|null,
     *     sex: string|null,
     *     father_id: int|null,
     *     mother_id: int|null,
     *     dod: string|null,
     *     yod: int|null,
     *     team_id: int|null,
     *     dob: string|null,
     *     yob: int|null,
     *     degree: int,
     *     sequence: string
     * }>
     */
    public function getAncestors(int $personId, int $teamId, int $maxDepth): Collection
    {
        return collect(DB::select($this->getRecursiveQuery(), [$personId, $teamId, $teamId, $maxDepth]));
    }

    /**
     * Build the recursive query for ancestors.
     *
     * PostgreSQL only allows a single recursive term, so both parents are visited
     * through a LATERAL VALUES list, letting each lookup use the primary key index.
     *
     * The sequence column doubles as a cycle guard: if a person's
     * id already appears in the ancestor chain, the join condition excludes them.
     * This prevents infinite loops caused by circular references in the data.
     *
     * REMARK: The maximum length of the comma separated sequence of all id's in the tree can NOT succeed 1024 characters!
     *         So, when largest id is 3 digits (max        999), the maximum level depth is 1024 / (3 + 1) = 256 levels
     *             when largest id is 4 digits (max      9.999), the maximum level depth is 1024 / (4 + 1) = 204 levels
     *             when largest id is 5 digits (max     99.999), the maximum level depth is 1024 / (5 + 1) = 170 levels
     *             when largest id is 6 digits (max    999.999), the maximum level depth is 1024 / (6 + 1) = 146 levels
     *             when largest id is 7 digits (max  9.999.999), the maximum level depth is 1024 / (7 + 1) = 128 levels
     *             when largest id is 8 digits (max 99.999.999), the maximum level depth is 1024 / (8 + 1) = 113 levels
     *             ...
     */
    private function getRecursiveQuery(): string
    {
        return "
            WITH RECURSIVE ancestors AS (
                SELECT
                    id, firstname, surname, sex, father_id, mother_id, dod, yod, team_id, dob, yob,
                    0 AS degree,
                    CAST(id AS VARCHAR(1024)) AS sequence
                FROM people
                WHERE deleted_at IS NULL AND id = ? AND team_id = ?

                UNION ALL

                SELECT
                    p.id, p.firstname, p.surname, p.sex, p.father_id, p.mother_id, p.dod, p.yod, p.team_id, p.dob, p.yob,
                    a.degree + 1 AS degree,
                    CAST(a.sequence || ',' || p.id AS VARCHAR(1024)) AS sequence
                FROM ancestors a
                CROSS JOIN LATERAL (VALUES (a.father_id), (a.mother_id)) AS parent(id)
                JOIN people p ON p.id = parent.id
                WHERE p.deleted_at IS NULL AND p.team_id = ? AND a.degree < ? AND POSITION(',' || p.id::text || ',' IN ',' || a.sequence || ',') = 0
            )

            SELECT * FROM ancestors
            ORDER BY degree, sex DESC;
        ";
    }
}

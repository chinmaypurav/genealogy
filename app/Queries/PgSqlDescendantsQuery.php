<?php

declare(strict_types=1);

namespace App\Queries;

use App\Contracts\DescendantsQueryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PgSqlDescendantsQuery implements DescendantsQueryInterface
{
    /**
     * Get descendants for a person up to a maximum depth.
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
    public function getDescendants(int $personId, int $teamId, int $maxDepth): Collection
    {
        return collect(DB::select($this->getRecursiveQuery(), [$personId, $teamId, $teamId, $maxDepth]));
    }

    /**
     * Build the recursive query for descendants.
     *
     * PostgreSQL only allows a single recursive term, so children of either parent
     * are matched with one OR join, which the planner resolves as a bitmap OR over
     * the father_id and mother_id indexes.
     *
     * The sequence column doubles as a cycle guard: if a person's
     * id already appears in the descendant chain, the join condition excludes them.
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
            WITH RECURSIVE descendants AS (
                SELECT
                    id, firstname, surname, sex, father_id, mother_id, dod, yod, team_id, dob, yob,
                    0 AS degree,
                    CAST(id AS VARCHAR(1024)) AS sequence
                FROM people
                WHERE deleted_at IS NULL AND id = ? AND team_id = ?

                UNION ALL

                SELECT
                    p.id, p.firstname, p.surname, p.sex, p.father_id, p.mother_id, p.dod, p.yod, p.team_id, p.dob, p.yob,
                    d.degree + 1 AS degree,
                    CAST(d.sequence || ',' || p.id AS VARCHAR(1024)) AS sequence
                FROM descendants d
                JOIN people p ON p.father_id = d.id OR p.mother_id = d.id
                WHERE p.deleted_at IS NULL AND p.team_id = ? AND d.degree < ? AND POSITION(',' || p.id::text || ',' IN ',' || d.sequence || ',') = 0
            )

            SELECT * FROM descendants
            ORDER BY degree, dob NULLS LAST, yob NULLS LAST;
        ";
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoupleFactory extends Factory
{
    public function definition(): array
    {
        $dateStart = $this->faker->optional()->date();
        $dateEnd   = $this->faker->optional()->dateTimeBetween($dateStart, '+20 years');

        /*
         * Partners default to fresh people so the factory never depends on existing rows.
         * Keys are resolved in order: the team follows person1, and a generated person2 joins that team.
         */
        return [
            'person1_id' => Person::factory(),
            'team_id'    => fn (array $attributes): ?int => Person::withoutGlobalScopes()->find($attributes['person1_id'])?->team_id,
            'person2_id' => fn (array $attributes): int => Person::factory()->create(['team_id' => $attributes['team_id']])->id,
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
            'is_married' => $this->faker->boolean(70),
            'has_ended'  => $dateEnd !== null,
        ];
    }
}

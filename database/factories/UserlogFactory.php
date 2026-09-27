<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Userlog>
 */
final class UserlogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Always generate in UTC (no DST gaps ever)
        $createdAt = $this->randomUtcDateTime('-2 years', '-1 day');
        $updatedAt = $this->randomUtcDateTimeAfter($createdAt);

        return [
            'user_id'      => User::factory(),
            'country_name' => $this->faker->country(),
            'country_code' => $this->faker->countryCode(),
            'created_at'   => $createdAt,
            'updated_at'   => $updatedAt,
        ];
    }

    private function randomUtcDateTime(string $start, string $end): CarbonInterface
    {
        return Date::parse(
            $this->faker->dateTimeBetween($start, $end, 'UTC')->format('Y-m-d H:i:s'),
            'UTC'
        );
    }

    private function randomUtcDateTimeAfter(CarbonInterface $after): CarbonInterface
    {
        // Convert to string so Faker can handle it
        $start = $after->format('Y-m-d H:i:s');

        return Date::parse(
            $this->faker->dateTimeBetween($start, 'now', 'UTC')->format('Y-m-d H:i:s'),
            'UTC'
        );
    }
}

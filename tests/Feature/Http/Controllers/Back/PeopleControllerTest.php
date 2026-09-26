<?php

declare(strict_types=1);

use App\Models\Person;
use Illuminate\Support\Collection;

test('birthdays lists upcoming birthdays across the year boundary in order', function (): void {
    /** @var Tests\TestCase $this */
    $this->travelTo('2026-12-15 10:00:00');

    $member = $this->memberWithRole('manager');
    $teamId = $member->current_team_id;

    $december = Person::factory()->create(['team_id' => $teamId, 'dob' => '1980-12-20']);
    $today    = Person::factory()->create(['team_id' => $teamId, 'dob' => '1975-12-15']);
    $january  = Person::factory()->create(['team_id' => $teamId, 'dob' => '1990-01-10']);
    $boundary = Person::factory()->create(['team_id' => $teamId, 'dob' => '1985-02-15']);

    Person::factory()->create(['team_id' => $teamId, 'dob' => '1985-02-16']);
    Person::factory()->create(['team_id' => $teamId, 'dob' => '1970-12-14']);
    Person::factory()->create(['team_id' => $teamId, 'dob' => '1970-07-01']);
    Person::factory()->create(['team_id' => $teamId, 'dob' => null]);
    Person::factory()->create(['dob' => '1980-12-20']);

    $this->actingAs($member)
        ->get(route('people.birthdays'))
        ->assertOk()
        ->assertViewHas('people', fn (Collection $people): bool => $people->pluck('id')->all() === [
            $today->id,
            $december->id,
            $january->id,
            $boundary->id,
        ]);
});

test('birthdays within a single month respect both day boundaries', function (): void {
    /** @var Tests\TestCase $this */
    $this->travelTo('2026-03-10 10:00:00');

    $member = $this->memberWithRole('manager');
    $teamId = $member->current_team_id;

    $april = Person::factory()->create(['team_id' => $teamId, 'dob' => '1980-04-01']);
    $march = Person::factory()->create(['team_id' => $teamId, 'dob' => '1980-03-11']);
    $may   = Person::factory()->create(['team_id' => $teamId, 'dob' => '1980-05-10']);

    Person::factory()->create(['team_id' => $teamId, 'dob' => '1980-03-09']);
    Person::factory()->create(['team_id' => $teamId, 'dob' => '1980-05-11']);

    $this->actingAs($member)
        ->get(route('people.birthdays'))
        ->assertOk()
        ->assertViewHas('people', fn (Collection $people): bool => $people->pluck('id')->all() === [
            $march->id,
            $april->id,
            $may->id,
        ]);
});

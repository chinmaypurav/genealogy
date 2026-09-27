<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

beforeEach(function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $this->older   = Person::factory()->withUser($user)->create(['dob' => null, 'yob' => 1950]);
    $this->younger = Person::factory()->withUser($user)->create(['dob' => '2010-06-15', 'yob' => null]);
    $this->distant = Person::factory()->withUser($user)->create(['dob' => null, 'yob' => 1900]);
    $this->unknown = Person::factory()->withUser($user)->create(['dob' => null, 'yob' => null]);
});

test('the older than scope filters by year of birth when no date of birth is given', function (): void {
    $ids = Person::query()->olderThan(null, 1980)->pluck('id');

    expect($ids)
        ->toContain($this->older->id, $this->distant->id, $this->unknown->id)
        ->not->toContain($this->younger->id);
});

test('the younger than scope filters by year of birth when no date of birth is given', function (): void {
    $ids = Person::query()->youngerThan(null, 1980)->pluck('id');

    expect($ids)
        ->toContain($this->younger->id, $this->unknown->id)
        ->not->toContain($this->older->id, $this->distant->id);
});

test('the partner offset scope filters by year of birth when no date of birth is given', function (): void {
    $ids = Person::query()->partnerOffset(null, 1980)->pluck('id');

    expect($ids)
        ->toContain($this->older->id, $this->younger->id, $this->unknown->id)
        ->not->toContain($this->distant->id);
});

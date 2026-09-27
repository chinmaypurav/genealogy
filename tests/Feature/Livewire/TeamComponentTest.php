<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->withPersonalTeam()->create();
    $this->team  = $this->owner->currentTeam;

    $this->actingAs($this->owner);
});

test('counts and lists exclude soft-deleted people and couples with a deleted partner', function (): void {
    $alice   = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Alice', 'surname' => 'Adams']);
    $bob     = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Bob', 'surname' => 'Brown']);
    $deleted = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Gone', 'surname' => 'Ghost']);
    Person::factory()->create(['team_id' => User::factory()->withPersonalTeam()->create()->current_team_id]);

    $couple = Couple::factory()->create(['team_id' => $this->team->id, 'person1_id' => $alice->id, 'person2_id' => $bob->id]);
    Couple::factory()->create(['team_id' => $this->team->id, 'person1_id' => $alice->id, 'person2_id' => $deleted->id]);
    $deleted->delete();

    $component = Livewire::test('livewire::team')
        ->assertSet('teamCounts.persons', 2)
        ->set('activeTab', 'persons');

    expect(collect($component->instance()->paginatedData->items())->pluck('name')->all())
        ->toBe(['Alice Adams', 'Bob Brown']);

    $component->set('activeTab', 'couples');

    expect($component->instance()->paginatedData->items())->toBe([[
        'id'      => $couple->id,
        'person1' => ['id' => $alice->id, 'name' => 'Alice Adams', 'sex' => $alice->sex],
        'person2' => ['id' => $bob->id, 'name' => 'Bob Brown', 'sex' => $bob->sex],
    ]]);
});

test('search filters couples by either partner name', function (): void {
    $alice = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Alice', 'surname' => 'Adams']);
    $bob   = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Bob', 'surname' => 'Brown']);
    $carol = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Carol', 'surname' => 'Clark']);
    $dave  = Person::factory()->create(['team_id' => $this->team->id, 'firstname' => 'Dave', 'surname' => 'Davis']);

    Couple::factory()->create(['team_id' => $this->team->id, 'person1_id' => $alice->id, 'person2_id' => $bob->id]);
    Couple::factory()->create(['team_id' => $this->team->id, 'person1_id' => $carol->id, 'person2_id' => $dave->id]);

    $component = Livewire::test('livewire::team')
        ->set('activeTab', 'couples')
        ->set('search', 'Dav');

    expect(collect($component->instance()->paginatedData->items())->pluck('person1.id')->all())->toBe([$carol->id]);
});

test('users tab lists team members by surname', function (): void {
    $member = User::factory()->create(['firstname' => 'Zed', 'surname' => 'Aardvark']);
    $this->team->users()->attach($member->id, ['role' => 'member']);

    $component = Livewire::test('livewire::team')
        ->assertSet('teamCounts.users', 1);

    expect(collect($component->instance()->paginatedData->items())->pluck('name')->all())->toBe(['Zed Aardvark']);
});

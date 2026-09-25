<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\Team;
use App\Models\User;
use App\PersonPhotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Jetstream\Http\Livewire\DeleteTeamForm;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('teams can be deleted', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->ownedTeams()->save($team = Team::factory()->make([
        'personal_team' => false,
    ]));

    $team->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'test-role']
    );

    $component = Livewire::test(DeleteTeamForm::class, ['team' => $team->fresh()])
        ->call('deleteTeam');

    expect($team->fresh())->toBeNull();
    expect($otherUser->fresh()->teams)->toHaveCount(0);
});

test('personal teams cant be deleted', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $component = Livewire::test(DeleteTeamForm::class, ['team' => $user->currentTeam])
        ->call('deleteTeam')
        ->assertHasErrors(['team']);

    expect($user->currentTeam->fresh())->not->toBeNull();
});

test('photos of the team are deleted with the team', function (): void {
    Storage::fake('photos');

    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->ownedTeams()->save($team = Team::factory()->make([
        'personal_team' => false,
    ]));

    $person = Person::factory()->create(['team_id' => $team->id]);
    $other  = Person::factory()->create(['team_id' => $user->current_team_id]);

    new PersonPhotos($person)->save([UploadedFile::fake()->image('photo.jpg')]);
    new PersonPhotos($other)->save([UploadedFile::fake()->image('photo.jpg')]);

    $teamPhotoDirectory  = dirname(Person::withoutGlobalScopes()->find($person->id)->photo->getPath());
    $otherPhotoDirectory = dirname($other->fresh()->photo->getPath());

    Livewire::test(DeleteTeamForm::class, ['team' => $team->fresh()])
        ->call('deleteTeam');

    expect(is_dir($teamPhotoDirectory))->toBeFalse()
        ->and(is_dir($otherPhotoDirectory))->toBeTrue()
        ->and($other->fresh()->photo)->not->toBeNull();
});

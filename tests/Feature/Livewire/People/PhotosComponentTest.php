<?php

declare(strict_types=1);

use App\Enums\PersonMediaCollection;
use App\Enums\PersonPhotoConversion;
use App\Models\Person;
use App\PersonPhotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('photos');

    $this->editor = $this->memberWithRole('editor');
    $this->actingAs($this->editor);

    $this->person = Person::factory()->create(['team_id' => $this->editor->current_team_id]);
});

test('photos can be uploaded', function (): void {
    Livewire::test('people::edit.photos', ['person' => $this->person])
        ->set('uploads', [
            UploadedFile::fake()->image('first.jpg', 400, 400),
            UploadedFile::fake()->image('second.jpg', 400, 400),
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('photos_updated')
        ->assertSet('uploads', []);

    $photos = $this->person->fresh()->getMedia(PersonMediaCollection::Photos->value);

    expect($photos)->toHaveCount(2)
        ->and(new PersonPhotos($this->person->fresh())->primary()->id)->toBe($photos->first()->id);
});

test('uploads below the minimum dimensions are rejected', function (): void {
    Livewire::test('people::edit.photos', ['person' => $this->person])
        ->set('uploads', [UploadedFile::fake()->image('tiny.jpg', 50, 50)])
        ->call('save')
        ->assertHasErrors(['uploads.0' => 'dimensions']);

    expect($this->person->fresh()->getMedia(PersonMediaCollection::Photos->value))->toBeEmpty();
});

test('a photo can be set as primary and deleted', function (): void {
    new PersonPhotos($this->person)->save([
        UploadedFile::fake()->image('first.jpg'),
        UploadedFile::fake()->image('second.jpg'),
    ]);

    [$first, $second] = new PersonPhotos($this->person->fresh())->all()->pluck('id')->all();

    Livewire::test('people::edit.photos', ['person' => $this->person->fresh()])
        ->call('setPrimary', $second)
        ->assertDispatched('photos_updated');

    expect(new PersonPhotos($this->person->fresh())->primary()->id)->toBe($second);

    Livewire::test('people::edit.photos', ['person' => $this->person->fresh()])
        ->call('delete', $second)
        ->assertDispatched('photos_updated');

    expect(new PersonPhotos($this->person->fresh())->primary()->id)->toBe($first);
});

test('members without the person:update permission cannot manage photos', function (): void {
    $this->actingAs($member = $this->memberWithRole('member'));

    $person = Person::factory()->create(['team_id' => $member->current_team_id]);
    new PersonPhotos($person)->save([UploadedFile::fake()->image('photo.jpg')]);

    Livewire::test('people::edit.photos', ['person' => $person->fresh()])
        ->call('delete', new PersonPhotos($person->fresh())->primary()->id)
        ->assertForbidden();

    expect(new PersonPhotos($person->fresh())->primary())->not->toBeNull();
});

test('the gallery shows the primary photo in medium size linking to the large size', function (): void {
    new PersonPhotos($this->person)->save([
        UploadedFile::fake()->image('first.jpg'),
        UploadedFile::fake()->image('second.jpg'),
    ]);

    $photos = new PersonPhotos($this->person->fresh())->all();
    $second = $photos->last();

    new PersonPhotos($this->person->fresh())->setPrimary($second->id);

    Livewire::test('people::gallery', ['person' => $this->person->fresh()])
        ->assertSet('selected', 0)
        ->assertSee($second->getUrl(PersonPhotoConversion::Medium->value))
        ->assertSee($second->getUrl(PersonPhotoConversion::Large->value));
});

test('the ancestors tree shows small photos', function (): void {
    $father = Person::factory()->create(['sex' => 'm', 'team_id' => $this->editor->current_team_id]);
    $this->person->update(['father_id' => $father->id]);

    new PersonPhotos($father)->save([UploadedFile::fake()->image('father.jpg')]);

    Livewire::test('people::ancestors', ['person' => $this->person->fresh()])
        ->assertSee(new PersonPhotos($father->fresh())->primary()->getUrl(PersonPhotoConversion::Small->value));
});

<?php

declare(strict_types=1);

use App\Enums\PersonMediaCollection;
use App\Enums\PersonPhotoConversion;
use App\Models\Person;
use App\PersonPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('photos');
});

it('stores uploaded photos in the photos collection with the first one as primary', function (): void {
    $person = Person::factory()->create();

    $savedCount = new PersonPhotos($person)->save([
        UploadedFile::fake()->image('first.jpg', 800, 600),
        UploadedFile::fake()->image('second.png', 800, 600),
    ]);

    $photos = $person->getMedia(PersonMediaCollection::Photos->value);

    expect($savedCount)->toBe(2)
        ->and($photos)->toHaveCount(2)
        ->and($photos->first()->disk)->toBe('photos')
        ->and($photos->pluck('name')->all())->toBe(['first', 'second'])
        ->and(new PersonPhotos($person->fresh())->primary()->id)->toBe($photos->first()->id);
});

it('generates a webp conversion for every size', function (PersonPhotoConversion $conversion, int $expectedWidth, int $expectedHeight): void {
    $person = Person::factory()->create();

    new PersonPhotos($person)->save([UploadedFile::fake()->image('portrait.jpg', 2400, 3000)]);

    $media = new PersonPhotos($person->fresh())->primary();
    $path  = $media->getPath($conversion->value);

    expect($media->hasGeneratedConversion($conversion->value))->toBeTrue()
        ->and($path)->toEndWith("portrait-{$conversion->value}.webp")
        ->and(array_slice(getimagesize($path), 0, 2))->toBe([$expectedWidth, $expectedHeight]);
})->with([
    'small'  => [PersonPhotoConversion::Small, 192, 240],
    'medium' => [PersonPhotoConversion::Medium, 384, 480],
    'large'  => [PersonPhotoConversion::Large, 864, 1080],
]);

it('keeps the original upload untouched', function (): void {
    $person = Person::factory()->create();
    $upload = UploadedFile::fake()->image('original.png', 500, 400);
    $bytes  = file_get_contents($upload->getRealPath());

    new PersonPhotos($person)->save([$upload]);

    expect(file_get_contents(new PersonPhotos($person->fresh())->primary()->getPath()))->toBe($bytes);
});

it('stores photos from a file path without removing the source file', function (): void {
    $person = Person::factory()->create();
    $upload = UploadedFile::fake()->image('imported.jpg');
    $source = $upload->getRealPath();

    $savedCount = new PersonPhotos($person)->save([$source]);

    expect($savedCount)->toBe(1)
        ->and(file_exists($source))->toBeTrue()
        ->and(new PersonPhotos($person->fresh())->primary())->not->toBeNull();
});

it('rejects files that are not an accepted image type', function (): void {
    $person = Person::factory()->create();

    $savedCount = new PersonPhotos($person)->save([UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')]);

    expect($savedCount)->toBeNull()
        ->and($person->getMedia(PersonMediaCollection::Photos->value))->toBeEmpty();
});

it('has no primary photo after deleting the last photo', function (): void {
    $person = Person::factory()->create();
    $photos = new PersonPhotos($person);
    $photos->save([UploadedFile::fake()->image('photo.jpg')]);

    expect($photos->delete($photos->primary()->id))->toBeTrue()
        ->and($photos->primary())->toBeNull()
        ->and($person->fresh()->getMedia(PersonMediaCollection::Photos->value))->toBeEmpty();
});

it('selects a new primary when deleting the current primary photo', function (): void {
    $person = Person::factory()->create();
    $photos = new PersonPhotos($person);
    $photos->save([
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
    ]);

    [$first, $second] = $photos->all()->pluck('id')->all();

    $photos->delete($first);

    expect($photos->primary()->id)->toBe($second);
});

it('keeps the primary photo when deleting another photo', function (): void {
    $person = Person::factory()->create();
    $photos = new PersonPhotos($person);
    $photos->save([
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
    ]);

    [$first, $second] = $photos->all()->pluck('id')->all();

    $photos->delete($second);

    expect($photos->primary()->id)->toBe($first);
});

it('only sets photos of the person as primary', function (): void {
    $person = Person::factory()->create();
    $other  = Person::factory()->create();

    new PersonPhotos($person)->save([UploadedFile::fake()->image('mine.jpg'), UploadedFile::fake()->image('mine-too.jpg')]);
    new PersonPhotos($other)->save([UploadedFile::fake()->image('theirs.jpg')]);

    $photos               = new PersonPhotos($person);
    [$firstId, $secondId] = $photos->all()->pluck('id')->all();
    $foreignId            = new PersonPhotos($other)->primary()->id;

    expect($photos->setPrimary($foreignId))->toBeFalse()
        ->and($photos->delete($foreignId))->toBeFalse()
        ->and($photos->setPrimary($secondId))->toBeTrue()
        ->and($photos->primary()->id)->toBe($secondId)
        ->and(new PersonPhotos($person->fresh())->all()->pluck('id')->all())->toBe([$secondId, $firstId]);
});

it('deletes all photos and their files', function (): void {
    $person = Person::factory()->create();
    $photos = new PersonPhotos($person);
    $photos->save([UploadedFile::fake()->image('photo.jpg')]);

    $directory = dirname(new PersonPhotos($person->fresh())->primary()->getPath());

    $photos->deleteAll();

    expect($photos->primary())->toBeNull()
        ->and($person->fresh()->getMedia(PersonMediaCollection::Photos->value))->toBeEmpty()
        ->and(is_dir($directory))->toBeFalse();
});

it('removes the photos when a person is force deleted', function (): void {
    $person = Person::factory()->create();
    new PersonPhotos($person)->save([UploadedFile::fake()->image('photo.jpg')]);

    $directory = dirname(new PersonPhotos($person->fresh())->primary()->getPath());

    $person->forceDelete();

    expect(is_dir($directory))->toBeFalse();
});

it('resolves the primary photo urls of many people at once', function (): void {
    $first   = Person::factory()->create();
    $second  = Person::factory()->create();
    $without = Person::factory()->create();

    new PersonPhotos($first)->save([UploadedFile::fake()->image('first.jpg'), UploadedFile::fake()->image('first-2.jpg')]);
    new PersonPhotos($second)->save([UploadedFile::fake()->image('second.jpg')]);

    $conversion = PersonPhotoConversion::Small;

    expect(PersonPhotos::primaryUrls([$first->id, $second->id, $without->id], $conversion))->toBe([
        $first->id  => new PersonPhotos($first->fresh())->primary()->getUrl($conversion->value),
        $second->id => new PersonPhotos($second->fresh())->primary()->getUrl($conversion->value),
    ])
        ->and(PersonPhotos::primaryUrls([], $conversion))->toBe([]);
});

it('watermarks the conversions when enabled', function (): void {
    $source = UploadedFile::fake()->image('photo.jpg', 800, 600);

    config()->set('app.upload_photo.add_watermark', false);

    $plain = Person::factory()->create();
    new PersonPhotos($plain)->save([UploadedFile::fake()->createWithContent('photo.jpg', $source->getContent())]);

    config()->set('app.upload_photo.add_watermark', true);

    $watermarked = Person::factory()->create();
    new PersonPhotos($watermarked)->save([UploadedFile::fake()->createWithContent('photo.jpg', $source->getContent())]);

    $conversion = PersonPhotoConversion::Large->value;

    expect(md5_file(new PersonPhotos($watermarked->fresh())->primary()->getPath($conversion)))
        ->not->toBe(md5_file(new PersonPhotos($plain->fresh())->primary()->getPath($conversion)));
});

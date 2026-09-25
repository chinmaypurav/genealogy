<?php

declare(strict_types=1);

use App\Gedcom\Export\GedcomFormatter;
use App\Gedcom\Export\GedcomMediaBuilder;
use App\Models\Person;
use App\PersonPhotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('media objects reference the original photo files with unique archive names', function (): void {
    Storage::fake('photos');

    $first  = Person::factory()->create();
    $second = Person::factory()->create();

    new PersonPhotos($first)->save([UploadedFile::fake()->image('portrait.jpg')]);
    new PersonPhotos($second)->save([UploadedFile::fake()->image('portrait.jpg')]);

    $firstPhoto  = $first->fresh()->photo;
    $secondPhoto = $second->fresh()->photo;

    $builder = new GedcomMediaBuilder('zipmedia', new GedcomFormatter());
    $builder->collectMediaObjects(Person::query()->whereKey([$first->id, $second->id])->get());

    expect($builder->getMediaFiles())->toBe([
        "{$firstPhoto->id}_portrait.jpg"  => $firstPhoto->getPath(),
        "{$secondPhoto->id}_portrait.jpg" => $secondPhoto->getPath(),
    ])
        ->and($builder->buildMediaRecords())
        ->toContain("1 FILE {$firstPhoto->id}_portrait.jpg")
        ->toContain('2 FORM image/jpeg');
});

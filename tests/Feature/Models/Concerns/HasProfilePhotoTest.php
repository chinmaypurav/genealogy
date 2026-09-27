<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('updating the photo stores the new file and deletes the old one', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();

    $user->updateProfilePhoto(UploadedFile::fake()->image('first.jpg'));
    $firstPath = $user->profile_photo_path;

    $user->updateProfilePhoto(UploadedFile::fake()->image('second.jpg'));

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($user->profile_photo_path);

    expect($user->profile_photo_url)->toBe(Storage::disk('public')->url($user->profile_photo_path));
});

test('deleting the photo removes the file and falls back to an initials avatar', function (): void {
    Storage::fake('public');

    $user = User::factory()->create(['firstname' => 'Ada', 'surname' => 'Lovelace']);
    $user->updateProfilePhoto(UploadedFile::fake()->image('photo.jpg'));
    $path = $user->profile_photo_path;

    $user->deleteProfilePhoto();

    Storage::disk('public')->assertMissing($path);

    expect($user->fresh()->profile_photo_path)->toBeNull()
        ->and($user->profile_photo_url)->toContain('ui-avatars.com/api/?name=A+L');
});

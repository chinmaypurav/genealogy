<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores a user's profile photo on the public disk and exposes its URL, with an initials avatar as fallback.
 *
 * Copied from Jetstream so existing users.profile_photo_path values and files keep working unchanged.
 */
trait HasProfilePhoto
{
    public function updateProfilePhoto(UploadedFile $photo, string $storagePath = 'profile-photos'): void
    {
        $previous = $this->profile_photo_path;

        $this->forceFill([
            'profile_photo_path' => $photo->storePublicly($storagePath, ['disk' => $this->profilePhotoDisk()]),
        ])->save();

        if ($previous) {
            Storage::disk($this->profilePhotoDisk())->delete($previous);
        }
    }

    public function deleteProfilePhoto(): void
    {
        if (is_null($this->profile_photo_path)) {
            return;
        }

        Storage::disk($this->profilePhotoDisk())->delete($this->profile_photo_path);

        $this->forceFill(['profile_photo_path' => null])->save();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->profile_photo_path
            ? Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path)
            : $this->defaultProfilePhotoUrl());
    }

    protected function defaultProfilePhotoUrl(): string
    {
        $initials = mb_trim(collect(explode(' ', (string) $this->name))->map(fn (string $segment): string => mb_substr($segment, 0, 1))->join(' '));

        return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&color=7F9CF5&background=EBF4FF';
    }

    protected function profilePhotoDisk(): string
    {
        return 'public';
    }
}

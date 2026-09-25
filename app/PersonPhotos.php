<?php

declare(strict_types=1);

namespace App;

use App\Enums\PersonMediaCollection;
use App\Enums\PersonPhotoConversion;
use App\Models\Person;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Manages the photos of a person, stored in the media library "photos" collection.
 * The primary photo is referenced by the person's photo_id.
 */
final readonly class PersonPhotos
{
    public function __construct(private Person $person) {}

    /**
     * Resolve the conversion URLs of many photos with a single query.
     *
     * @param  iterable<int, int|null>  $mediaIds
     * @return array<int, string> URLs keyed by media id
     */
    public static function urls(iterable $mediaIds, PersonPhotoConversion $conversion): array
    {
        $mediaIds = collect($mediaIds)->filter()->unique();

        if ($mediaIds->isEmpty()) {
            return [];
        }

        return Media::query()
            ->whereKey($mediaIds->all())
            ->get()
            ->mapWithKeys(fn (Media $media): array => [$media->id => $media->getUrl($conversion->value)])
            ->all();
    }

    /**
     * Add photos to the person. The first photo becomes primary when the person has none.
     *
     * @param  array<int, UploadedFile|string>  $photos  Uploaded files or absolute file paths
     * @return int|null Number of successfully saved photos, null if none were saved
     */
    public function save(array $photos): ?int
    {
        $savedCount = 0;

        foreach ($photos as $photo) {
            try {
                $adder = $photo instanceof UploadedFile
                    ? $this->person->addMedia($photo)->usingName(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME))
                    : $this->person->addMedia($photo)->preservingOriginal();

                $media = $adder->toMediaCollection(PersonMediaCollection::Photos->value);

                if ($this->person->photo_id === null) {
                    $this->person->update(['photo_id' => $media->id]);
                }

                $savedCount++;
            } catch (Throwable $e) {
                Log::error('Failed to save photo', [
                    'person_id' => $this->person->id,
                    'team_id'   => $this->person->team_id,
                    'error'     => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        if ($savedCount > 0) {
            $this->person->unsetRelation('media');
        }

        return $savedCount ?: null;
    }

    /**
     * @return Collection<int, Media>
     */
    public function all(): Collection
    {
        return $this->person->getMedia(PersonMediaCollection::Photos->value)->toBase();
    }

    public function find(int $mediaId): ?Media
    {
        return $this->all()->firstWhere('id', $mediaId);
    }

    /**
     * Delete a photo. When it was the primary photo, the next available photo becomes primary.
     */
    public function delete(int $mediaId): bool
    {
        $media = $this->find($mediaId);

        if (! $media) {
            return false;
        }

        $media->delete();
        $this->person->unsetRelation('media');

        if ($this->person->photo_id === $mediaId) {
            $this->person->update(['photo_id' => $this->all()->first()?->id]);
        }

        return true;
    }

    /**
     * Delete all photos and clear the primary photo.
     */
    public function deleteAll(): void
    {
        $this->person->clearMediaCollection(PersonMediaCollection::Photos->value);
        $this->person->update(['photo_id' => null]);
    }

    public function setPrimary(int $mediaId): bool
    {
        if (! $this->find($mediaId)) {
            return false;
        }

        return $this->person->update(['photo_id' => $mediaId]);
    }
}

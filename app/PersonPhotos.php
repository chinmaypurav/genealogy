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
 * The first photo of the collection (by order_column) is the primary photo.
 */
final readonly class PersonPhotos
{
    public function __construct(private Person $person) {}

    /**
     * Resolve the primary photo conversion URLs of many people with a single query.
     *
     * @param  iterable<int, int>  $personIds
     * @return array<int, string> URLs keyed by person id
     */
    public static function primaryUrls(iterable $personIds, PersonPhotoConversion $conversion): array
    {
        $personIds = collect($personIds)->filter()->unique();

        if ($personIds->isEmpty()) {
            return [];
        }

        return Media::query()
            ->where('model_type', new Person()->getMorphClass())
            ->where('collection_name', PersonMediaCollection::Photos->value)
            ->whereIn('model_id', $personIds->all())
            ->orderBy('order_column')
            ->get()
            ->unique('model_id')
            ->mapWithKeys(fn (Media $media): array => [(int) $media->model_id => $media->getUrl($conversion->value)])
            ->all();
    }

    /**
     * Add photos to the person, after the existing ones.
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

                $adder->toMediaCollection(PersonMediaCollection::Photos->value);

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

        $this->person->unsetRelation('media');

        return $savedCount ?: null;
    }

    /**
     * @return Collection<int, Media>
     */
    public function all(): Collection
    {
        return $this->person->getMedia(PersonMediaCollection::Photos->value)->toBase();
    }

    public function primary(): ?Media
    {
        return $this->person->getFirstMedia(PersonMediaCollection::Photos->value);
    }

    public function find(int $mediaId): ?Media
    {
        return $this->all()->firstWhere('id', $mediaId);
    }

    /**
     * Delete a photo. When it was the primary photo, the next photo automatically becomes primary.
     */
    public function delete(int $mediaId): bool
    {
        $media = $this->find($mediaId);

        if (! $media) {
            return false;
        }

        $media->delete();
        $this->person->unsetRelation('media');

        return true;
    }

    public function deleteAll(): void
    {
        $this->person->clearMediaCollection(PersonMediaCollection::Photos->value);
        $this->person->unsetRelation('media');
    }

    /**
     * Make a photo primary by moving it to the front of the collection.
     */
    public function setPrimary(int $mediaId): bool
    {
        $ids = $this->all()->pluck('id');

        if (! $ids->contains($mediaId)) {
            return false;
        }

        Media::setNewOrder($ids->reject(fn (int $id): bool => $id === $mediaId)->prepend($mediaId)->all());

        $this->person->unsetRelation('media');

        return true;
    }
}

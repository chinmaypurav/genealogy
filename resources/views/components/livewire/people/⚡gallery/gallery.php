<?php

declare(strict_types=1);

use App\Enums\PersonPhotoConversion;
use App\Models\Person;
use App\PersonPhotos;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new class extends Component
{
    public Person $person;

    /** @var array<int, array{id: int, medium: string, large: string}> */
    public array $images = [];

    public ?int $selected = null;

    // ------------------------------------------------------------------------------
    #[On('photos_updated')]
    public function mount(): void
    {
        $this->images = new PersonPhotos($this->person)->all()
            ->map(fn (Media $media): array => [
                'id'     => $media->id,
                'medium' => $media->getUrl(PersonPhotoConversion::Medium->value),
                'large'  => $media->getUrl(PersonPhotoConversion::Large->value),
            ])
            ->values()
            ->all();

        $this->selected = $this->getPrimaryImageIndex();
    }

    public function previousImage(): void
    {
        if (count($this->images) === 0) {
            return;
        }

        $this->selected = ($this->selected - 1 + count($this->images)) % count($this->images);
    }

    public function nextImage(): void
    {
        if (count($this->images) === 0) {
            return;
        }

        $this->selected = ($this->selected + 1) % count($this->images);
    }

    public function selectImage(?int $index): void
    {
        $this->selected = $index;
    }

    /**
     * Get the index of the primary image, falling back to the first image.
     */
    protected function getPrimaryImageIndex(): ?int
    {
        if (empty($this->images)) {
            return null;
        }

        $index = collect($this->images)->search(fn (array $image): bool => $image['id'] === $this->person->photo_id);

        return $index !== false ? $index : 0;
    }
};

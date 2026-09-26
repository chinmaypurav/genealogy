<?php

declare(strict_types=1);

use App\Enums\PersonMediaCollection;
use App\Enums\PersonPhotoConversion;
use App\Models\Person;
use App\PersonPhotos;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new class extends Component
{
    public Person $person;

    /**
     * @var Collection<int, string> Medium photo URLs
     */
    #[Locked]
    public Collection $images;

    /**
     * @var Collection<int, Media>
     */
    #[Locked]
    public Collection $files;

    public function mount(): void
    {
        $this->images = new PersonPhotos($this->person)->all()
            ->map(fn (Media $media): string => $media->getUrl(PersonPhotoConversion::Medium->value));

        $this->files = $this->person->getMedia(PersonMediaCollection::Files->value);
    }
};

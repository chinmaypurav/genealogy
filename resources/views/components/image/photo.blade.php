@props(['person' => null])

@use('App\Enums\PersonMediaCollection')
@use('App\Enums\PersonPhotoConversion')

@php
    $photoUrl = $person->getFirstMediaUrl(PersonMediaCollection::Photos->value, PersonPhotoConversion::Medium->value);
@endphp

<div class="user-image">
    @if ($photoUrl)
        <img
            {{ $attributes->merge(['class' => 'w-full rounded-sm shadow-lg dark:shadow-black/30']) }}
            src="{{ $photoUrl }}"
            alt="{{ $person->name }}"
            title="{{ $person->name }}"
        />
    @else
        <x-svg.person-no-image
            {{ $attributes->merge(['class' => 'w-full rounded-sm shadow-lg dark:shadow-black/30 fill-neutral-400']) }}
            alt="no-image-found"
        />
    @endif

    @if ($person->isDeceased())
        <div class="ribbon">{{ __('person.deceased') }}</div>
    @endif
</div>

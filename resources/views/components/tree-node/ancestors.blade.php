@props(['person', 'ancestors', 'photo_urls' => [], 'level_current' => 0, 'level_max'])

@php
    $level_current++;

    $person_sequence = $ancestors->firstWhere('id', $person->id)->sequence;

    $ancestors_next = $ancestors->where('degree', $level_current)->filter(function ($item) use ($person_sequence): bool {
        return str_starts_with($item->sequence, $person_sequence . ',');
    });
@endphp

<li>
    @if ($person)
        <x-link href="/people/{{ $person->id }}" title="{{ $person->sex === 'm' ? __('app.male') : __('app.female') }}">
            <figure class="w-24">
                <div class="user-image">
                    @if (isset($photo_urls[$person->photo_id]))
                        <img
                            src="{{ $photo_urls[$person->photo_id] }}"
                            class="w-full rounded-sm shadow-lg dark:shadow-black/30"
                            alt="{{ $person->id }}"
                        />
                    @else
                        <x-svg.person-no-image
                            class="w-full rounded-sm fill-neutral-400 shadow-lg dark:shadow-black/30"
                            alt="no-image-found"
                        />
                    @endif

                    @if ($person->dod or $person->yod)
                        <div class="ribbon" title="{{ __('person.deceased') }}">&nbsp;</div>
                    @endif
                </div>

                <figcaption
                    @class([
                        'text-red-600 dark:text-red-400'         => $person->dod or $person->yod,
                        'text-primary-500 dark:text-primary-300' => ! ($person->dod or $person->yod),
                        'line-clamp-2 text-xs leading-tight w-24 wrap-break-word',
                    ])
                    title="{{ implode(' ', array_filter([$person->firstname, $person->surname])) }}"
                >
                    {{ implode(' ', array_filter([$person->firstname, $person->surname])) }}
                </figcaption>
            </figure>
        </x-link>

        {{-- ancestors (recursive) --}}
        @if ($level_current < $level_max)
            @if (count($ancestors_next) > 0)
                <ul>
                    @foreach ($ancestors_next as $ancestor)
                        <x-tree-node.ancestors
                            :person="$ancestor"
                            :ancestors="$ancestors"
                            :photo_urls="$photo_urls"
                            :level_current="$level_current"
                            :level_max="$level_max"
                        />
                    @endforeach
                </ul>
            @endif
        @endif
    @endif
</li>

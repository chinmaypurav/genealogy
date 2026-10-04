<x-dropdown align="right" width="48">
    <x-slot name="trigger">
        <button type="button" title="{{ __('app.language_select') }}">{{ strtoupper(app()->getLocale()) }}</button>
    </x-slot>

    <x-slot name="content">
        <div class="block px-4 py-2 text-xs text-gray-400">{{ __('app.language_select') }}</div>

        @foreach (config('app.available_locales') as $locale_name => $available_locale)
            @if ($available_locale === app()->getLocale())
                <x-dropdown-link href="#" :active="true"> {{ $locale_name }} </x-dropdown-link>
            @else
                <form method="POST" action="{{ route('language.update', $available_locale) }}" x-data>
                    @csrf

                    <x-dropdown-link href="{{ route('language.update', $available_locale) }}" @click.prevent="$root.submit()"> {{ $locale_name }} </x-dropdown-link>
                </form>
            @endif
        @endforeach
    </x-slot>
</x-dropdown>

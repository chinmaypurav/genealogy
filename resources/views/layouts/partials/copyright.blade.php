<div class="flex justify-between bg-neutral-200 p-2 text-xs dark:bg-neutral-700">
    <!-- Left Section: Copyright and Licensing -->
    <div class="text-left">
        <p>Copyright © {{ Date::now()->year }} | Genealogy.</p>
        <p>
            {{ __('app.open_source') }}
            <x-link href="/about" aria-label="Read about the license">{{ __('app.licence') }}</x-link>.
        </p>
        <p>{{ __('app.free_use') }}.</p>
    </div>
</div>

<footer class="text-center text-neutral-600 lg:text-left dark:text-neutral-200 print:hidden">
    <!-- Top Section: Social Media Links -->
    <div class="flex items-center justify-center border-b-2 border-neutral-200 bg-neutral-200 p-2 lg:justify-between dark:border-neutral-500 dark:bg-neutral-700">
        <!-- Social Media Header (Visible on Large Screens) -->
        <div class="mr-12 hidden lg:block">
            <span>{{ __('app.connected_social') }}:</span>
        </div>

        <!-- Social Media Icons -->
        <div class="flex justify-center">
            <a href="https://github.com/chinmaypurav/genealogy" class="" target="_blank" aria-label="Visit GitHub" title="GitHub">
                <x-ts-icon icon="tabler.brand-github" class="text-neutral-900 dark:text-neutral-200" />
            </a>
        </div>
    </div>

    <!-- Middle Section: Main Content -->
    <div class="bg-neutral-100 p-2 text-center md:text-left dark:bg-neutral-600">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
            <!-- Logo Section -->
            <div class="flex justify-center md:justify-start">
                <a href="{{ route('home') }}" aria-label="Go to Home" title="Home">
                    <x-svg.genealogy
                        class="hover:fill-primary-300 dark:hover:fill-primary-300 size-48 dark:fill-neutral-400"
                        alt="Genealogy Logo"
                    />
                </a>
            </div>

            <!-- Useful Links Section -->
            <div>
                <h6 class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
                    {{ __('app.useful_links') }}
                </h6>
                <x-hr.narrow class="my-4 h-1 w-48 rounded-sm border-0 bg-gray-100 max-md:mx-auto dark:bg-gray-700" />
                <p class="mb-4">
                    <x-nav-link-footer href="{{ route('about') }}" :active="request()->routeIs('about')">
                        {{ __('app.about') }}
                    </x-nav-link-footer>
                </p>
                <p class="mb-4">
                    <x-nav-link-footer href="{{ route('help') }}" :active="request()->routeIs('help')">
                        {{ __('app.help') }}
                    </x-nav-link-footer>
                </p>
                <p class="mb-4">
                    <x-nav-link-footer
                        href="{{ route('password.generator') }}"
                        :active="request()->routeIs('password.generator')"
                    >
                        {{ __('app.password_generator') }}
                    </x-nav-link-footer>
                </p>
            </div>

            <!-- Impressum Section -->
            <div>
                <h6 class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
                    {{ __('app.impressum') }}
                </h6>
                <x-hr.narrow class="my-4 h-1 w-48 rounded-sm border-0 bg-gray-100 max-md:mx-auto dark:bg-gray-700" />
                <p class="mb-4">
                    <x-nav-link-footer href="{{ url('terms-of-service') }}" :active="request()->is('terms-of-service')">
                        {{ __('app.terms_of_service') }}
                    </x-nav-link-footer>
                </p>
                <p class="mb-4">
                    <x-nav-link-footer href="{{ url('privacy-policy') }}" :active="request()->is('privacy-policy')">
                        {{ __('app.privacy_policy') }}
                    </x-nav-link-footer>
                </p>
            </div>

            <!-- Contact Section -->
            <div>
                <h6 class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
                    {{ __('app.contact') }}
                </h6>
                <x-hr.narrow class="my-4 h-1 w-48 rounded-sm border-0 bg-gray-100 max-md:mx-auto dark:bg-gray-700" />
                <p class="mb-4 flex items-center justify-center md:justify-start">
                    <x-ts-icon icon="home" class="mr-3 inline-block size-5" />
                    New York, NY 10012, US
                </p>
                <p class="mb-4 flex items-center justify-center md:justify-start">
                    <x-ts-icon icon="tabler.mail" class="mr-3 inline-block size-5" />
                    info@example.com
                </p>
                <p class="mb-4 flex items-center justify-center md:justify-start">
                    <x-ts-icon icon="tabler.phone" class="mr-3 inline-block size-5" />
                    + 01 234 567 88
                </p>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Copyright -->
    @include('layouts.partials.copyright')
</footer>

<?php

namespace App\Providers;

use App\Models\Userlog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;
use Stevebauman\Location\Facades\Location;
use TallStackUi\Facades\TallStackUi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // -----------------------------------------------------------------------
        // Language select for guest users
        // Language will be overruled by language as defined in each authenticated user profile
        // -----------------------------------------------------------------------
        view()->composer('components.set.language', function ($view) {
            $view->with('current_locale', app()->getLocale());
            $view->with('available_locales', config('app.available_locales'));
        });

        // -----------------------------------------------------------------------
        // LOG-VIEWER : grant access (in production) to developer
        // -----------------------------------------------------------------------
        LogViewer::auth(function ($request) {
            return auth()->check() && auth()->user()->is_developer;
        });

        // -----------------------------------------------------------------------
        // LOG-VIEWER : log all queries (not in production)
        // -----------------------------------------------------------------------
        // if (! app()->isProduction()) {
        //     DB::listen(function ($query) {
        //         logger(Str::replaceArray('?', $query->bindings, $query->sql));
        //     });
        // }

        // -----------------------------------------------------------------------
        // LOG-VIEWER : log all N+1 queries
        // -----------------------------------------------------------------------
        Model::preventLazyLoading();

        Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
            Log::warning("N+1 Query detected.\r\n" . sprintf('N+1 Query detected in model %s on relation %s.', get_class($model), $relation));
        });

        // -----------------------------------------------------------------------
        // LOG-VIEWER : log all requests
        // -----------------------------------------------------------------------
        // This is done by the middleware \Http\Middleware\LogAllRequests.php
        // and can be enabled/disable in \bootstrap\app.php

        // -----------------------------------------------------------------------
        // log users (except developers, only in production)
        // -----------------------------------------------------------------------
        if (app()->isProduction()) {
            Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
                try {
                    if ($position = Location::get()) {
                        $country_name = $position->countryName;
                        $country_code = $position->countryCode;
                    } else {
                        $country_name = null;
                        $country_code = null;
                    }

                    Userlog::create([
                        'user_id'      => $event->user->id,
                        'country_name' => $country_name,
                        'country_code' => $country_code,
                    ]);
                } catch (QueryException $e) {
                    Log::error("User log ERROR: {$e->getMessage()}");
                }
            });
        }

        // -----------------------------------------------------------------------
        // TallStackUI personalization
        // Ref : https://tallstackui.com/docs/personalization/soft
        // -----------------------------------------------------------------------
        TallStackUi::personalize()->button()
            ->block('wrapper.class')->replace('gap-x-2', 'gap-x-0');

        TallStackUi::personalize()->tab()
            ->block('base.wrapper')->replace('rounded-lg', 'rounded')
            ->block('base.wrapper')->replace('dark:bg-dark-700', 'dark:bg-neutral-700')
            ->block('item.select')->replace('dark:text-dark-300', 'dark:text-neutral-50');

        TallStackUi::personalize()->slide()
            ->block('wrapper.first')->replace('bg-opacity-50', 'bg-opacity-20')
            ->block('wrapper.fifth')->replace('dark:bg-dark-700', 'dark:bg-dark-900')
            ->block('footer')->append('dark:text-secondary-600');

        TallStackUi::personalize()->card()
            ->block('wrapper.first')->replace('gap-4', 'gap-2')
            ->block('wrapper.second')->replace('rounded-lg', 'rounded')
            ->block('header.wrapper', 'dark:border-b-dark-600 flex items-center justify-between border-b border-b-gray-100 p-2')
            ->block('footer.wrapper', 'text-secondary-700 dark:text-dark-300 dark:border-t-dark-600 rounded rounded-t-none border-t p-2')
            ->block('footer.text', 'flex items-center justify-end gap-2');

        // -----------------------------------------------------------------------
        // timezone management
        // -----------------------------------------------------------------------
        Carbon::macro('inApplicationTimezone', function () {
            return $this->tz(config('app.timezone_display'));
        });

        Carbon::macro('inUserTimezone', function () {
            return $this->tz(auth()->user()?->timezone ?? config('app.timezone_display'));
        });

        // -----------------------------------------------------------------------
        // about
        // -----------------------------------------------------------------------
        AboutCommand::add('Application', [
            'Name'    => 'Genealogy',
            'author'  => 'kreaweb.be',
            'github'  => 'https://github.com/MGeurts/genealogy',
            'license' => 'MIT License',
        ]);
        // -----------------------------------------------------------------------
    }
}

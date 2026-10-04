<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Stores the visitor's chosen locale in the session; App\Http\Middleware\Localization applies it on later requests.
 *
 * The route constrains {locale} to config('app.available_locales'), so callers can rely on only known locales reaching the session.
 */
class SwitchLanguageController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        session()->put('locale', $locale);

        return back(303);
    }
}

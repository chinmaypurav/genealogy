<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Support\LocalizedMarkdown;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function home(): View
    {
        $homeFile = LocalizedMarkdown::path(app()->getLocale() . '/' . 'home.md');

        if ($homeFile === null) {
            abort(404, 'Home page file not found');
        }

        $content = file_get_contents($homeFile);

        if ($content === false) {
            abort(404, 'Home page content not found');
        }

        return view('home', [
            'home' => Str::markdown($content),
        ]);
    }

    public function about(): View
    {
        $aboutFile = LocalizedMarkdown::path(app()->getLocale() . '/' . 'about.md');

        if ($aboutFile === null) {
            abort(404, 'About page file not found');
        }

        $markdown = file_get_contents($aboutFile);

        if ($markdown === false) {
            abort(404, 'About page content not found');
        }

        // First render as Blade (to process {{ Date::now()->year }}, etc.)
        $compiledBlade = Blade::render($markdown);

        // Then parse the rendered Blade output as Markdown
        return view('about', [
            'about' => Str::markdown($compiledBlade),
        ]);
    }

    public function help(): View
    {
        $helpFile = LocalizedMarkdown::path('help.md');

        if ($helpFile === null) {
            abort(404, 'Help page file not found');
        }

        $content = file_get_contents($helpFile);

        if ($content === false) {
            abort(404, 'Help page content not found');
        }

        return view('help', [
            'help' => Str::markdown($content),
        ]);
    }

    public function terms(): View
    {
        return view('terms', ['terms' => $this->renderMarkdown('terms.md')]);
    }

    public function policy(): View
    {
        return view('policy', ['policy' => $this->renderMarkdown('policy.md')]);
    }

    protected function renderMarkdown(string $name): string
    {
        $path = LocalizedMarkdown::path($name);

        abort_if($path === null, 404);

        return Str::markdown((string) file_get_contents($path));
    }
}

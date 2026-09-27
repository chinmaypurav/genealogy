<?php

declare(strict_types=1);

use App\Support\LocalizedMarkdown;

test('prefers the translation for the current locale', function (): void {
    app()->setLocale('de');

    expect(LocalizedMarkdown::path('terms.md'))->toBe(resource_path('markdown/terms.de.md'));
});

test('falls back to the untranslated file', function (): void {
    app()->setLocale('de');

    expect(LocalizedMarkdown::path('help.md'))->toBe(resource_path('markdown/help.md'));
});

test('returns null when no file exists', function (): void {
    expect(LocalizedMarkdown::path('missing.md'))->toBeNull();
});

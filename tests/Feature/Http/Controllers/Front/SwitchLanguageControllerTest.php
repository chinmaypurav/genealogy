<?php

declare(strict_types=1);

test('visitors can switch to an available locale', function (): void {
    $this->post(route('language.update', 'nl'))->assertRedirect();

    expect(session('locale'))->toBe('nl');
});

test('unknown locales are rejected without touching the session', function (string $locale): void {
    $this->post(url('language/' . $locale))->assertNotFound();

    expect(session('locale'))->toBeNull();
})->with(['xx', '..%2Fetc', 'en..']);

test('the locale cannot be switched with a GET request', function (): void {
    $this->get(url('language/nl'))->assertMethodNotAllowed();

    expect(session('locale'))->toBeNull();
});

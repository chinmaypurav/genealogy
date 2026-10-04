<?php

declare(strict_types=1);

test('an available session locale is applied', function (): void {
    $this->withSession(['locale' => 'nl']);

    $this->get(route('home'))->assertOk();

    expect(app()->getLocale())->toBe('nl');
});

test('an unknown session locale is ignored instead of breaking the request', function (): void {
    $this->withSession(['locale' => 'foo/bar']);

    $this->get(route('home'))->assertOk();

    expect(app()->getLocale())->toBe(config('app.locale'));
});

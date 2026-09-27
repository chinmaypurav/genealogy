<?php

declare(strict_types=1);

test('the application returns a successful response', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('public pages return a successful response', function (string $routeName): void {
    $this->get(route($routeName))->assertOk();
})->with(['home', 'about', 'help']);

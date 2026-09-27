<?php

declare(strict_types=1);

test('public pages render', function (string $route): void {
    $this->get(route($route))->assertOk();
})->with(['home', 'about', 'help', 'terms.show', 'policy.show']);

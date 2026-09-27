<?php

declare(strict_types=1);

test('terms of service and privacy policy pages render', function (string $route): void {
    $this->get(route($route))->assertOk();
})->with(['terms.show', 'policy.show']);

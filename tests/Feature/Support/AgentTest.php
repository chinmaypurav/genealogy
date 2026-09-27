<?php

declare(strict_types=1);

use App\Support\Agent;

test('detects desktop platform and browser', function (): void {
    $agent = new Agent();
    $agent->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

    expect($agent->platform())->toBe('Windows')
        ->and($agent->browser())->toBe('Chrome')
        ->and($agent->isDesktop())->toBeTrue();
});

test('detects mobile platform and browser', function (): void {
    $agent = new Agent();
    $agent->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1');

    expect($agent->platform())->toBe('iOS')
        ->and($agent->browser())->toBe('Safari')
        ->and($agent->isDesktop())->toBeFalse();
});

test('reports unknown platform and browser without a user agent', function (): void {
    $agent = new Agent();

    expect($agent->platform())->toBeNull()
        ->and($agent->browser())->toBeNull();
});

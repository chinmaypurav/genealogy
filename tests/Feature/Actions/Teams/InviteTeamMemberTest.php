<?php

declare(strict_types=1);

use App\Actions\Teams\InviteTeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

test('rejects roles that are not in the teams config', function (): void {
    Mail::fake();

    $owner = User::factory()->withPersonalTeam()->create();

    try {
        app(InviteTeamMember::class)->invite($owner, $owner->currentTeam, 'new@example.com', 'superuser');
    } finally {
        expect($owner->currentTeam->teamInvitations()->count())->toBe(0);
        Mail::assertNothingSent();
    }
})->throws(ValidationException::class);

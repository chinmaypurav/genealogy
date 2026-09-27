<?php

declare(strict_types=1);

use App\Mail\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Features;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('team members can be invited to team', function (): void {
    if (! Features::sendsTeamInvitations()) {
        $this->markTestSkipped('Team invitations not enabled.');
    }

    Mail::fake();

    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $component = Livewire::test('teams::team-member-manager', ['team' => $user->currentTeam])
        ->set('addTeamMemberForm', [
            'email' => 'test@example.com',
            'role'  => 'administrator',
        ])->call('addTeamMember');

    Mail::assertSent(TeamInvitation::class);

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(1);
});

test('team member invitations can be cancelled', function (): void {
    if (! Features::sendsTeamInvitations()) {
        $this->markTestSkipped('Team invitations not enabled.');
    }

    Mail::fake();

    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    // Add the team member...
    $component = Livewire::test('teams::team-member-manager', ['team' => $user->currentTeam])
        ->set('addTeamMemberForm', [
            'email' => 'test@example.com',
            'role'  => 'administrator',
        ])->call('addTeamMember');

    $invitationId = $user->currentTeam->fresh()->teamInvitations->first()->id;

    // Cancel the team invitation...
    $component->call('cancelTeamInvitation', $invitationId);

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(0);
});

test('invited users can accept the invitation from the email link', function (): void {
    $owner   = User::factory()->withPersonalTeam()->create();
    $invitee = User::factory()->withPersonalTeam()->create();

    $invitation = $owner->currentTeam->teamInvitations()->create(['email' => $invitee->email, 'role' => 'editor']);

    $acceptUrl = new TeamInvitation($invitation)->content()->with['acceptUrl'];

    $this->actingAs($invitee)->get($acceptUrl)->assertRedirect();

    expect($invitee->fresh()->hasTeamRole($owner->currentTeam, 'editor'))->toBeTrue()
        ->and($owner->currentTeam->teamInvitations()->count())->toBe(0);
});

test('members who cannot remove team members cannot cancel invitations', function (): void {
    $member     = $this->memberWithRole('editor');
    $invitation = $member->currentTeam->teamInvitations()->create(['email' => 'someone@example.com', 'role' => 'member']);

    $this->actingAs($member);

    Livewire::test('teams::team-member-manager', ['team' => $member->currentTeam])
        ->call('cancelTeamInvitation', $invitation->id)
        ->assertForbidden();

    expect($invitation->fresh())->not->toBeNull();
});

<?php

declare(strict_types=1);

use App\Actions\Teams\InviteTeamMember;
use App\Actions\Teams\RemoveTeamMember;
use App\Actions\Teams\UpdateTeamMemberRole;
use App\Models\Team;
use App\Models\User;
use App\Support\TeamRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public bool $currentlyManagingRole = false;

    public ?User $managingRoleFor = null;

    public ?string $currentRole = null;

    public bool $confirmingLeavingTeam = false;

    public bool $confirmingTeamMemberRemoval = false;

    public ?int $teamMemberIdBeingRemoved = null;

    /** @var array{email: string, role: string|null} */
    public array $addTeamMemberForm = [
        'email' => '',
        'role'  => null,
    ];

    /**
     * New members join by invitation, so they can accept (or register first) before getting access.
     */
    public function addTeamMember(): void
    {
        $this->resetErrorBag();

        app(InviteTeamMember::class)->invite($this->user, $this->team, $this->addTeamMemberForm['email'], $this->addTeamMemberForm['role']);

        $this->reset('addTeamMemberForm');

        $this->team = $this->team->fresh();

        $this->dispatch('saved');
    }

    public function cancelTeamInvitation(int $invitationId): void
    {
        Gate::authorize('removeTeamMember', $this->team);

        $this->team->teamInvitations()->whereKey($invitationId)->delete();

        $this->team = $this->team->fresh();
    }

    public function manageRole(int $userId): void
    {
        $this->currentlyManagingRole = true;
        $this->managingRoleFor       = User::findOrFail($userId);
        $this->currentRole           = $this->managingRoleFor->teamRole($this->team)?->key;
    }

    public function updateRole(): void
    {
        app(UpdateTeamMemberRole::class)->update($this->user, $this->team, $this->managingRoleFor->id, $this->currentRole ?? '');

        $this->team = $this->team->fresh();

        $this->stopManagingRole();
    }

    public function stopManagingRole(): void
    {
        $this->currentlyManagingRole = false;
    }

    public function leaveTeam(): void
    {
        app(RemoveTeamMember::class)->remove($this->user, $this->team, $this->user);

        $this->confirmingLeavingTeam = false;

        $this->redirect(config('fortify.home'));
    }

    public function confirmTeamMemberRemoval(int $userId): void
    {
        $this->confirmingTeamMemberRemoval = true;
        $this->teamMemberIdBeingRemoved    = $userId;
    }

    public function removeTeamMember(): void
    {
        app(RemoveTeamMember::class)->remove($this->user, $this->team, User::findOrFail($this->teamMemberIdBeingRemoved));

        $this->confirmingTeamMemberRemoval = false;
        $this->teamMemberIdBeingRemoved    = null;

        $this->team = $this->team->fresh();
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    /**
     * @return list<TeamRole>
     */
    #[Computed]
    public function roles(): array
    {
        return TeamRole::all();
    }
};

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Back;

use App\Actions\Teams\AddTeamMember;
use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use TallStackUi\Traits\Interactions;

/**
 * Accepts a team invitation from the signed link in the invitation email.
 *
 * The member is added on the team owner's behalf, because the invitee has no permission on the
 * team yet; the signed URL is what proves the invitation is genuine.
 */
class AcceptTeamInvitationController extends Controller
{
    use Interactions;

    public function __invoke(TeamInvitation $invitation): RedirectResponse
    {
        $team = $invitation->team;

        abort_if($team === null, 404);

        app(AddTeamMember::class)->add($team->owner, $team, $invitation->email, $invitation->role);

        $invitation->delete();

        $this->toast()->success(__('Great! You have accepted the invitation to join the :team team.', ['team' => $team->name]))->send();

        return redirect()->to(config('fortify.home'));
    }
}

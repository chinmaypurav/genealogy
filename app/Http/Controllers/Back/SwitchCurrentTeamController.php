<?php

declare(strict_types=1);

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switches the user's current team, which scopes every people and couples query.
 */
class SwitchCurrentTeamController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $team = Team::findOrFail($request->integer('team_id'));

        abort_unless($request->user()?->switchTeam($team) === true, 403);

        return redirect()->to(config('fortify.home'), 303);
    }
}

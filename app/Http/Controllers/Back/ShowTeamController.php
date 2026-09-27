<?php

declare(strict_types=1);

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\View\View;

/**
 * Shows a team's settings page. Access is checked by the route's `can:view,team` middleware.
 */
class ShowTeamController extends Controller
{
    public function __invoke(Team $team): View
    {
        return view('teams.show', ['team' => $team]);
    }
}

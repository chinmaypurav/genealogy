<?php

declare(strict_types=1);

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferTeamOwnershipRequest;
use App\Models\Team;
use App\Notifications\OwnershipTransferred;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use TallStackUi\Traits\Interactions;

/**
 * Handles team ownership transfers.
 *
 * Authorization and eligibility of the new owner live in TransferTeamOwnershipRequest.
 * Side effects (activity log, notification, success toast) only run once the
 * ownership change has committed, so a failed transfer never reports success.
 */
class TeamController extends Controller
{
    use Interactions;

    public function transferOwnership(TransferTeamOwnershipRequest $request, Team $team): RedirectResponse
    {
        $currentOwner = $team->owner;
        $newOwner     = $request->newOwner();

        try {
            DB::transaction(function () use ($team, $currentOwner, $newOwner): void {
                $currentOwnerMembership = $team->users()->find($currentOwner->id);

                // The previous owner stays on the team as an administrator.
                if (! $currentOwnerMembership) {
                    $team->users()->attach($currentOwner->id, ['role' => 'administrator']);
                } elseif (empty($currentOwnerMembership->membership->role)) {
                    $team->users()->updateExistingPivot($currentOwner->id, ['role' => 'administrator']);
                }

                // Owners are tracked on the team itself, not in the membership pivot.
                $team->users()->detach($newOwner->id);

                $team->user_id = $newOwner->id;
                $team->save();
            });
        } catch (Exception $exception) {
            report($exception);

            $this->toast()->error(__('team.transfer'), __('team.transfer_failed'))->flash()->send();

            return back();
        }

        defer(function () use ($team, $currentOwner, $newOwner): void {
            activity()
                ->useLog('user_team')
                ->performedOn($team)
                ->causedBy($currentOwner)
                ->event(__('app.event_transferred'))
                ->withProperties([
                    'email' => $newOwner->email,
                    'name'  => $newOwner->name,
                ])
                ->log(__('team.membership') . ' ' . __('app.event_transferred'));
        });

        // The transfer has committed; a mail failure must not turn it into an error response.
        rescue(fn () => $newOwner->notify(new OwnershipTransferred($team)));

        $this->toast()->success(__('team.transfer'), __('team.transferred_to') . e($newOwner->name) . '.')->flash()->send();

        return back();
    }
}

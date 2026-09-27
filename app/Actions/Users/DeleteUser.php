<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Teams\DeleteTeam;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a user account with its owned teams, profile photo and API tokens, in one transaction.
 */
class DeleteUser
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->deleteTeams($user);
            $user->deleteProfilePhoto();
            $user->tokens->each->delete();
            $user->delete();
        });
    }

    /**
     * Delete the teams and team associations attached to the user.
     */
    protected function deleteTeams(User $user): void
    {
        $user->teams()->detach();

        foreach ($user->ownedTeams as $team) {
            app(DeleteTeam::class)->delete($team);
        }
    }
}

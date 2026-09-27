<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\PersonMediaCollection;
use App\Models\Person;
use App\Models\Team;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Permanently deletes a team, including its people's photos on disk.
 *
 * Moves the acting user to another team first so they never end up on a deleted one.
 */
class DeleteTeam implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Team $team): void
    {
        $this->deletePhotos($team);

        $user = auth()->user();

        // If the user is currently on this team, switch to another team if available
        if ($user && $user->current_team_id === $team->id) {
            $newTeam = $user->allTeams()->where('id', '!=', $team->id)->first();

            $user->forceFill([
                'current_team_id' => $newTeam?->id,
            ])->save();
        }

        // Permanently delete the team
        $team->purge();
    }

    /**
     * Delete the photos of all people in the team, including their files on disk.
     */
    protected function deletePhotos(Team $team): void
    {
        Media::query()
            ->where('model_type', new Person()->getMorphClass())
            ->where('collection_name', PersonMediaCollection::Photos->value)
            ->whereIn('model_id', Person::query()->withoutGlobalScopes()->where('team_id', $team->id)->select('id'))
            ->each(fn (Media $media): ?bool => $media->delete());
    }
}

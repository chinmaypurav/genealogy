<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\PersonMediaCollection;
use App\Models\Person;
use App\Models\Team;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class DeleteTeam implements DeletesTeams
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
    private function deletePhotos(Team $team): void
    {
        Media::query()
            ->where('model_type', new Person()->getMorphClass())
            ->where('collection_name', PersonMediaCollection::Photos->value)
            ->whereIn('model_id', Person::query()->withoutGlobalScopes()->where('team_id', $team->id)->select('id'))
            ->each(fn (Media $media): ?bool => $media->delete());
    }
}

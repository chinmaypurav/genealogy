<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A family tree workspace: people, couples and the users collaborating on them.
 *
 * Owns team membership: its owner, members with their roles, and pending invitations.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $personal_team
 * @property-read User $owner
 */
final class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'personal_team',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_team')
            ->setDescriptionForEvent(function (string $eventName): string {
                return __('team.team') . ' ' . __('app.event_' . $eventName);
            })
            ->logOnly([
                'name',
                'description',
                'personal_team',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $user = auth()->user();

        if (! $user) {
            $activity->team_id = null;

            return;
        }

        $currentTeam = $user->currentTeam;

        // Don't set team_id if this team is being deleted or if no current team exists
        if (! $currentTeam || $currentTeam->id === $this->id) {
            // Try to use the user's personal team as fallback
            $personalTeam      = $user->personalTeam();
            $activity->team_id = $personalTeam?->id;
        } else {
            $activity->team_id = $currentTeam->id;
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Members of the team, excluding the owner.
     *
     * @return BelongsToMany<User, $this, Membership, 'membership'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, Membership::class)
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * @return HasMany<TeamInvitation, $this>
     */
    public function teamInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function hasUserWithEmail(string $email): bool
    {
        return $this->users->concat([$this->owner])->contains('email', $email);
    }

    public function removeUser(User $user): void
    {
        if ($user->current_team_id === $this->id) {
            $user->forceFill(['current_team_id' => null])->save();
        }

        $this->users()->detach($user);
    }

    /**
     * Detach every member and delete the team, clearing it as anyone's current team first.
     */
    public function purge(): void
    {
        $this->owner()->where('current_team_id', $this->id)->update(['current_team_id' => null]);

        $this->users()->where('current_team_id', $this->id)->update(['current_team_id' => null]);

        $this->users()->detach();

        $this->delete();
    }

    public function isDeletable(): bool
    {
        // Prevent deletion of personal teams
        if ($this->personal_team) {
            return false;
        }

        // Use exists() queries instead of loading relationships
        // This only counts records without loading them into memory
        if ($this->users()->exists()) {
            return false;
        }

        if ($this->persons()->exists()) {
            return false;
        }

        if ($this->couples()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Returns ALL PERSONS (n Person)
     *
     * @return HasMany<Person, $this>
     */
    public function persons(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /**
     * Returns ALL COUPLES (n Couple)
     *
     * @return HasMany<Couple, $this>
     */
    public function couples(): HasMany
    {
        return $this->hasMany(Couple::class);
    }

    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }
}

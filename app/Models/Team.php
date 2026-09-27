<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $personal_team
 * @property-read User $owner
 */
final class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\PersonFactory> */
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

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /* -------------------------------------------------------------------------------------------- */
    // Log activities
    /* -------------------------------------------------------------------------------------------- */
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
            $activity->team_id = $personalTeam->id;
        } else {
            $activity->team_id = $currentTeam->id;
        }
    }

    /* -------------------------------------------------------------------------------------------- */
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

    /* -------------------------------------------------------------------------------------------- */
    // Relations
    /* -------------------------------------------------------------------------------------------- */
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

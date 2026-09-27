<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public User $user;

    /** @var array<string, int> */
    public array $teamCounts = [];

    /** One of: users, persons, couples. */
    public string $activeTab = 'users';

    /** One of: 5, 10, 25, 50, 100. */
    public int $perPage = 10;

    public string $search = '';

    public function mount(): void
    {
        $authUser = auth()->user();

        if ($authUser === null) {
            abort(401);
        }

        $this->user = User::with('currentTeam:id,name')->findOrFail($authUser->id);

        $this->loadTeamCounts();
    }

    #[Computed]
    public function paginatedData(): LengthAwarePaginator
    {
        return match ($this->activeTab) {
            'users'   => $this->getPaginatedUsers(),
            'persons' => $this->getPaginatedPersons(),
            'couples' => $this->getPaginatedCouples(),
            default   => $this->getPaginatedUsers()
        };
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    protected function team(): Team
    {
        return $this->user->currentTeam;
    }

    protected function loadTeamCounts(): void
    {
        $team = $this->team();

        $this->teamCounts = [
            'users'   => $team->users()->count(),
            'persons' => $team->persons()->count(),
            'couples' => $team->couples()->count(),
        ];
    }

    /**
     * @param  Builder<User>|Builder<Person>  $query
     */
    protected function applyNameSearch(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereLike('firstname', "%{$this->search}%")
                ->orWhereLike('surname', "%{$this->search}%");
        });
    }

    /**
     * @return array{id: int, name: string, sex: string|null}
     */
    protected function personData(Person $person): array
    {
        return [
            'id'   => $person->id,
            'name' => $person->name,
            'sex'  => $person->sex,
        ];
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int, name: string}>
     */
    protected function getPaginatedUsers(): LengthAwarePaginator
    {
        return $this->team()->users()
            ->select('users.id', 'users.firstname', 'users.surname')
            ->when($this->search, fn (Builder $query) => $this->applyNameSearch($query))
            ->orderBy('users.surname')
            ->orderBy('users.firstname')
            ->paginate($this->perPage)
            ->through(fn (User $user): array => [
                'id'   => $user->id,
                'name' => $user->name,
            ]);
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int, name: string, sex: string|null}>
     */
    protected function getPaginatedPersons(): LengthAwarePaginator
    {
        return $this->team()->persons()
            ->select('id', 'firstname', 'surname', 'sex')
            ->when($this->search, fn (Builder $query) => $this->applyNameSearch($query))
            ->orderBy('surname')
            ->orderBy('firstname')
            ->paginate($this->perPage)
            ->through(fn (Person $person): array => $this->personData($person));
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int, person1: array{id: int, name: string, sex: string|null}, person2: array{id: int, name: string, sex: string|null}}>
     */
    protected function getPaginatedCouples(): LengthAwarePaginator
    {
        $person1Column = fn (string $column): Builder => Person::select($column)->whereColumn('people.id', 'couples.person1_id');

        return $this->team()->couples()
            ->with(['person1:id,firstname,surname,sex', 'person2:id,firstname,surname,sex'])
            ->whereHas('person1')
            ->whereHas('person2')
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereHas('person1', fn (Builder $query) => $this->applyNameSearch($query))
                        ->orWhereHas('person2', fn (Builder $query) => $this->applyNameSearch($query));
                });
            })
            ->orderBy($person1Column('surname'))
            ->orderBy($person1Column('firstname'))
            ->paginate($this->perPage)
            ->through(fn (Couple $couple): array => [
                'id'      => $couple->id,
                'person1' => $this->personData($couple->person1),
                'person2' => $this->personData($couple->person2),
            ]);
    }
};

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Back;

use App\Actions\People\SearchRelationshipCandidates;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRelationshipCandidatesRequest;
use App\Models\Couple;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Serves compact, authorized relationship-picker results to TallStackUI.
 *
 * A dedicated endpoint keeps the typeahead payload out of Livewire state and
 * makes team isolation enforceable before any candidate names are returned.
 */
class SearchRelationshipCandidatesController extends Controller
{
    public function __invoke(SearchRelationshipCandidatesRequest $request, Person $person, string $relationship): JsonResponse
    {
        Gate::authorize('view', $person);
        Gate::authorize('create', $relationship === 'partner' ? Couple::class : Person::class);

        return response()->json(
            app(SearchRelationshipCandidates::class)->handle(
                $person,
                $relationship,
                (string) $request->input('search', ''),
                $request->selectedPersonIds(),
            )
        );
    }
}

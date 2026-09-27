<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// frontend routes
Route::livewire('password-generator', 'livewire::password-generator')->name('password.generator');

Route::controller(App\Http\Controllers\Front\PageController::class)->group(function (): void {
    Route::get('/', 'home')->name('home');
    Route::get('about', 'about')->name('about');
    Route::get('help', 'help')->name('help');
    Route::get('terms-of-service', 'terms')->name('terms.show');
    Route::get('privacy-policy', 'policy')->name('policy.show');
});

// profile: reachable before email verification, so the verify-email page can link to it
Route::middleware([
    'auth:sanctum',
    App\Http\Middleware\AuthenticateSession::class,
])->group(function (): void {
    Route::view('user/profile', 'profile.show')->name('profile.show');
});

// backend routes
Route::middleware([
    'auth:sanctum',
    App\Http\Middleware\AuthenticateSession::class,
    'verified',
])->group(function (): void {
    // teams
    Route::view('teams/create', 'teams.create')->name('teams.create')->can('create', App\Models\Team::class);
    Route::get('teams/{team}', App\Http\Controllers\Back\ShowTeamController::class)->name('teams.show')->can('view', 'team');
    Route::put('current-team', App\Http\Controllers\Back\SwitchCurrentTeamController::class)->name('current-team.update');
    Route::get('team-invitations/{invitation}', App\Http\Controllers\Back\AcceptTeamInvitationController::class)
        ->middleware('signed')
        ->name('team-invitations.accept');

    // pages
    Route::livewire('team', 'livewire::team')->name('team');
    Route::livewire('teamlog', 'livewire::teamlog')->name('teamlog');
    Route::livewire('peoplelog', 'livewire::peoplelog')->name('peoplelog');

    Route::controller(App\Http\Controllers\Back\TeamController::class)->group(function (): void {
        Route::put('/teams/{team}/transfer-ownership', 'transferOwnership')->name('teams.transfer-ownership');
    });

    // people
    Route::controller(App\Http\Controllers\Back\PeopleController::class)->group(function (): void {
        Route::get('search', 'search')->name('people.search');
        Route::get('birthdays', 'birthdays')->name('people.birthdays');

        Route::get('people/add', 'add')->name('people.add')->can('create', App\Models\Person::class);
        Route::get('people/{person}', 'show')->name('people.show');
        Route::get('people/{person}/ancestors', 'ancestors')->name('people.ancestors');
        Route::get('people/{person}/descendants', 'descendants')->name('people.descendants');
        Route::get('people/{person}/chart', 'chart')->name('people.chart');
        Route::get('people/{person}/history', 'history')->name('people.history');
        Route::get('people/{person}/datasheet', 'datasheet')->name('people.datasheet');
        Route::get('people/{person}/timeline', 'timeline')->name('people.timeline');
        Route::get('people/{person}/add-father', 'addFather')->name('people.add-father')->can('create', App\Models\Person::class);
        Route::get('people/{person}/add-mother', 'addMother')->name('people.add-mother')->can('create', App\Models\Person::class);
        Route::get('people/{person}/add-child', 'addChild')->name('people.add-child')->can('create', App\Models\Person::class);
        Route::get('people/{person}/add-partner', 'addPartner')->name('people.add-partner')->can('create', App\Models\Couple::class);
        Route::get('people/{person}/edit-contact', 'editContact')->name('people.edit-contact')->can('update', 'person');
        Route::get('people/{person}/edit-death', 'editDeath')->name('people.edit-death')->can('update', 'person');
        Route::get('people/{person}/edit-events', 'editEvents')->name('people.edit-events')->can('update', 'person');
        Route::get('people/{person}/edit-family', 'editFamily')->name('people.edit-family')->can('update', 'person');
        Route::get('people/{person}/edit-files', 'editFiles')->name('people.edit-files')->can('update', 'person');
        Route::get('people/{person}/edit-photos', 'editPhotos')->name('people.edit-photos')->can('update', 'person');
        Route::get('people/{person}/edit-profile', 'editProfile')->name('people.edit-profile')->can('update', 'person');
        Route::get('people/{person}/{couple}/edit-partner', 'editPartner')->name('people.edit-partner')->can('update', 'couple');
    });

    Route::get('people/{person}/relationship-candidates/{relationship}', App\Http\Controllers\Back\SearchRelationshipCandidatesController::class)
        ->whereIn('relationship', ['father', 'mother', 'child', 'partner'])
        ->name('people.relationship-candidates');

    // gedcom
    Route::livewire('exportteam', 'gedcom::exportteam')->name('gedcom.exportteam');
    Route::livewire('importteam', 'gedcom::importteam')->name('gedcom.importteam');
});

// set application language in session
// actual language switching wil be handled by App\Http\Middleware\Localization::class
Route::get('language/{locale}', function ($locale) {
    session()->put('locale', $locale);

    return back();
});

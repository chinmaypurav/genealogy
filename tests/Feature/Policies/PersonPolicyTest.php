<?php

declare(strict_types=1);

use App\Models\Person;

test('members without person permissions cannot open the add and edit pages', function (): void {
    $member = $this->memberWithRole('member');
    $person = Person::factory()->create(['team_id' => $member->current_team_id]);

    $this->actingAs($member);

    $this->get(route('people.add'))->assertForbidden()->assertSee(__('app.unauthorized_access'));
    $this->get(route('people.add-child', $person))->assertForbidden();
    $this->get(route('people.edit-profile', $person))->assertForbidden();
});

test('editors can open the add and edit pages', function (): void {
    $editor = $this->memberWithRole('editor');
    $person = Person::factory()->create(['team_id' => $editor->current_team_id]);

    $this->actingAs($editor);

    $this->get(route('people.add'))->assertOk();
    $this->get(route('people.add-child', $person))->assertOk();
    $this->get(route('people.edit-profile', $person))->assertOk();
});

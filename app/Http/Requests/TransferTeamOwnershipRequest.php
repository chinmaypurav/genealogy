<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorizes and validates a team ownership transfer.
 *
 * Only the owner of a non-personal team may transfer it, and only to a user who
 * is already a member of that team (never to the current owner). Keeping these
 * guards here lets the controller assume a valid, eligible new owner.
 */
class TransferTeamOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->team()->personal_team && $this->user()->can('update', $this->team());
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'new_owner_id' => [
                'required',
                'integer',
                Rule::in($this->team()->users()->whereKeyNot($this->team()->user_id)->pluck('users.id')),
            ],
        ];
    }

    public function team(): Team
    {
        return $this->route('team');
    }

    public function newOwner(): User
    {
        return User::findOrFail($this->validated('new_owner_id'));
    }
}

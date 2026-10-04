<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\Activity;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        // create administrator user
        $administrator = User::factory([
            'firstname' => '_',
            'surname'   => 'Administrator',
            'email'     => 'administrator@genealogy.test',
        ])->withPersonalTeam()->create();

        Activity::defaultCauser($administrator);

        // create manager user
        User::factory([
            'firstname' => '_',
            'surname'   => 'Manager',
            'email'     => 'manager@genealogy.test',
        ])->withPersonalTeam()->create();

        // create editor user
        User::factory([
            'firstname' => '_',
            'surname'   => 'Editor',
            'email'     => 'editor@genealogy.test',
        ])->withPersonalTeam()->create();

        // create normal users (members)
        for ($i = 1; $i <= 6; $i++) {
            User::factory([
                'firstname' => '__',
                'surname'   => 'Member ' . $i,
                'email'     => 'member_' . $i . '@genealogy.test',
            ])->withPersonalTeam()->create();
        }

        for ($i = 7; $i <= 10; $i++) {
            User::factory([
                'firstname' => '___',
                'surname'   => 'Member ' . $i,
                'email'     => 'member_' . $i . '@genealogy.test',
            ])->withPersonalTeam()->create();
        }
    }
}

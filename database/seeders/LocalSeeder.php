<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;
use Illuminate\Database\Seeder;

final class LocalSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()
            ->withPersonalTeam()
            ->create([
                'name' => 'Manuk Minasyan',
                'email' => 'manuk.minasyan1@gmail.com',
            ]);

        People::factory()->for($user->personalTeam(), 'team')->count(500)->create();

        Company::factory()->for($user->personalTeam(), 'team')->count(50)->create();

        Opportunity::factory()->for($user->personalTeam(), 'team')->count(100)->create();
    }
}

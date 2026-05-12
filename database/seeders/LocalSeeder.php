<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class LocalSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command->info('Skipping local seeding as the environment is not local.');

            return;
        }

        $this->call(SystemAdministratorSeeder::class);

        $user = User::factory()
            ->withPersonalTeam()
            ->create([
                'name' => 'Manuk Minasyan',
                'email' => 'manuk.minasyan1@gmail.com',
            ]);

        $team = $user->personalTeam();
        $teamId = $team->id;

        User::factory()
            ->count(10)
            ->create()
            ->each(function (User $member) use ($teamId): void {
                $member->teams()->attach($teamId, ['role' => 'member']);
            });

        $this->call(PortfolioSeeder::class, parameters: ['user' => $user, 'team' => $team]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Relaticle\SystemAdmin\Enums\SystemAdministratorRole;
use App\Models\SystemAdministrator;
use Illuminate\Database\Seeder;

final class SystemAdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemAdministrator::firstOrCreate(
            ['email' => 'admin@relaticle.com'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('password'),
                'role' => SystemAdministratorRole::SuperAdministrator,
                'email_verified_at' => now(),
            ]
        );
    }
}

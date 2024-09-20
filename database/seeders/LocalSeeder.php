<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocalSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Manuk Minasyan',
            'email' => 'manuk@minasyan.info',
        ]);

        User::factory()->count(50)->create();

        Company::factory()->count(50)->create();
    }
}

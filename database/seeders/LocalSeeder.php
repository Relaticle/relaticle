<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;

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

        // Set the current user and tenant.
        Auth::setUser($user);
        Filament::setTenant($user->personalTeam());

        // Create people.
        People::factory()->for($user->personalTeam(), 'team')->count(500)->create();

        $customFields = CustomField::query()
            ->whereIn('code', ['icp', 'domain_name'])
            ->get()
            ->keyBy('code');

        Company::factory()
            ->for($user->personalTeam(), 'team')
            ->count(50)
            ->afterCreating(function (Company $company) use ($customFields) {
                $company->saveCustomFieldValue($customFields->get('domain_name'), 'https://' . fake()->domainName());
                $company->saveCustomFieldValue($customFields->get('icp'), fake()->boolean(70));
            })
            ->create();

        Opportunity::factory()->for($user->personalTeam(), 'team')->count(100)->create();
    }
}

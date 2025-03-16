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
use Relaticle\CustomFields\Models\CustomFieldOption;

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
            ->whereIn('code', ['icp', 'stage', 'domain_name'])
            ->get()
            ->keyBy('code');


        Company::factory()
            ->for($user->personalTeam(), 'team')
            ->count(50)
            ->afterCreating(function (Company $company) use ($customFields): void {
                $company->saveCustomFieldValue($customFields->get('domain_name'), 'https://' . fake()->domainName());
                $company->saveCustomFieldValue($customFields->get('icp'), fake()->boolean(70));
            })
            ->create();

        Opportunity::factory()->for($user->personalTeam(), 'team')
            ->count(150)
            ->afterCreating(function (Opportunity $opportunity) use ($customFields): void {
                $opportunity->saveCustomFieldValue($customFields->get('stage'), $customFields->get('stage')->options->random()->id);
            })
            ->create();
    }
}

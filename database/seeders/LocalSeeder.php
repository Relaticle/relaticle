<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
//
//        User::factory()
//            ->withPersonalTeam()
//            ->create([
//                'name' => 'Test User',
//                'email' => 'test@example.com',
//            ]);
//
//        // Create 10 Test Users
//        User::factory()->withPersonalTeam()->count(10)->create();
//
//        // Set the current user and tenant.
//        Auth::setUser($user);
//        Filament::setTenant($user->personalTeam());
//
//        $customFields = CustomField::query()
//            ->whereIn('code', ['icp', 'stage', 'domain_name'])
//            ->get()
//            ->keyBy('code');
//
//        Company::factory()
//            ->for($user->personalTeam(), 'team')
//            ->count(50)
//            ->afterCreating(function (Company $company) use ($customFields): void {
//                $company->saveCustomFieldValue($customFields->get('domain_name'), 'https://'.fake()->domainName());
//                $company->saveCustomFieldValue($customFields->get('icp'), fake()->boolean(70));
//            })
//            ->create();
//
//        // Create people.
//        People::factory()
//            ->for($user->personalTeam(), 'team')
//            ->for($user->currentTeam->companies->random(), 'company')
//            ->state(new Sequence(
//                fn (Sequence $sequence): array => ['company_id' => $user->personalTeam()->companies->random()->id]
//            ))
//            ->count(500)->create();
//
//        // Create opportunities.
//        Opportunity::factory()->for($user->personalTeam(), 'team')
//            ->count(150)
//            ->afterCreating(function (Opportunity $opportunity) use ($customFields): void {
//                $opportunity->saveCustomFieldValue($customFields->get('stage'), $customFields->get('stage')->options->random()->id);
//            })
//            ->create();
    }
}

<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\ActivityLog\Contracts\TenantResolver;
use Relaticle\ActivityLog\Models\Activity;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('resolves tenant via the bound TenantResolver', function (): void {
    $resolver = app(TenantResolver::class);
    expect($resolver->resolve())->toBe($this->team->getKey());
});

it('scopes activity queries to the current tenant', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Scoped Co']);
    $activities = Activity::query()
        ->where('subject_type', 'company')
        ->where('subject_id', $company->getKey())
        ->get();
    expect($activities)->each(
        fn ($activity) => $activity->team_id->toBe($this->team->getKey())
    );
});

it('returns no results when no tenant is set', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Hidden Co']);
    Filament::setTenant(null);
    $activities = Activity::query()
        ->where('subject_type', 'company')
        ->where('subject_id', $company->getKey())
        ->get();
    expect($activities)->toBeEmpty();
});

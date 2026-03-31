<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Relaticle\ActivityLog\Filament\Schemas\AttributeHistory;
use Relaticle\ActivityLog\Models\Activity;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('shows edit history for a specific attribute', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'V1']);

    Activity::withoutGlobalScopes()->where('subject_id', $company->getKey())->delete();

    Activity::withoutGlobalScopes()->insert([
        [
            'log_name' => 'crm',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => 'company',
            'subject_id' => $company->getKey(),
            'team_id' => $this->team->getKey(),
            'causer_type' => (new User)->getMorphClass(),
            'causer_id' => $this->user->getKey(),
            'attribute_changes' => json_encode(['attributes' => ['name' => 'V2'], 'old' => ['name' => 'V1']]),
            'properties' => '[]',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ],
        [
            'log_name' => 'crm',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => 'company',
            'subject_id' => $company->getKey(),
            'team_id' => $this->team->getKey(),
            'causer_type' => (new User)->getMorphClass(),
            'causer_id' => $this->user->getKey(),
            'attribute_changes' => json_encode(['attributes' => ['name' => 'V3'], 'old' => ['name' => 'V2']]),
            'properties' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $component = Livewire::test(AttributeHistory::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
        'attribute' => 'name',
    ]);

    $data = $component->get('historyData');

    expect($data)->toHaveCount(2)
        ->and($data[0]['new_value'])->toBe('V3')
        ->and($data[0]['old_value'])->toBe('V2')
        ->and($data[1]['new_value'])->toBe('V2')
        ->and($data[1]['old_value'])->toBe('V1');
});

it('includes causer name and timestamp', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'V1']);

    Activity::withoutGlobalScopes()->where('subject_id', $company->getKey())->delete();

    Activity::withoutGlobalScopes()->insert([
        'log_name' => 'crm',
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'company',
        'subject_id' => $company->getKey(),
        'team_id' => $this->team->getKey(),
        'causer_type' => (new User)->getMorphClass(),
        'causer_id' => $this->user->getKey(),
        'attribute_changes' => json_encode(['attributes' => ['name' => 'V2'], 'old' => ['name' => 'V1']]),
        'properties' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AttributeHistory::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
        'attribute' => 'name',
    ]);

    $data = $component->get('historyData');

    expect($data[0])
        ->toHaveKey('causer_name')
        ->toHaveKey('created_at_human');
});

it('returns empty history when attribute has no changes', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'V1']);

    Activity::withoutGlobalScopes()->where('subject_id', $company->getKey())->delete();

    $component = Livewire::test(AttributeHistory::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
        'attribute' => 'name',
    ]);

    $data = $component->get('historyData');

    expect($data)->toBeEmpty();
});

it('filters changes to only the requested attribute', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'V1']);

    Activity::withoutGlobalScopes()->where('subject_id', $company->getKey())->delete();

    Activity::withoutGlobalScopes()->insert([
        'log_name' => 'crm',
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'company',
        'subject_id' => $company->getKey(),
        'team_id' => $this->team->getKey(),
        'causer_type' => (new User)->getMorphClass(),
        'causer_id' => $this->user->getKey(),
        'attribute_changes' => json_encode(['attributes' => ['phone' => '555'], 'old' => ['phone' => '444']]),
        'properties' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AttributeHistory::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
        'attribute' => 'name',
    ]);

    $data = $component->get('historyData');

    expect($data)->toBeEmpty();
});

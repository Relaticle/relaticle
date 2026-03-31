<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Relaticle\ActivityLog\Filament\Schemas\ActivityTimeline;
use Relaticle\ActivityLog\Models\Activity;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('groups timeline entries by date', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(14));

    $company = Company::factory()->for($this->team)->create(['name' => 'Test Co']);

    Activity::withoutGlobalScopes()->where('subject_id', $company->getKey())->delete();

    Activity::withoutGlobalScopes()->insert([
        [
            'log_name' => 'crm',
            'description' => 'created',
            'event' => 'created',
            'subject_type' => 'company',
            'subject_id' => $company->getKey(),
            'team_id' => $this->team->getKey(),
            'attribute_changes' => json_encode(['attributes' => ['name' => 'Test Co']]),
            'properties' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'log_name' => 'crm',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => 'company',
            'subject_id' => $company->getKey(),
            'team_id' => $this->team->getKey(),
            'attribute_changes' => json_encode(['attributes' => ['name' => 'Updated'], 'old' => ['name' => 'Test Co']]),
            'properties' => '[]',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ],
    ]);

    $component = Livewire::test(ActivityTimeline::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
    ]);

    $data = $component->get('timelineData');
    $groups = $data['groups'];

    expect($groups)->toHaveCount(2)
        ->and($groups[0]['label'])->toBe('Today')
        ->and($groups[0]['entries'])->toHaveCount(1)
        ->and($groups[1]['label'])->toBe('Yesterday')
        ->and($groups[1]['entries'])->toHaveCount(1);
});

it('includes causer initials when no avatar is present', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Test Co']);

    $component = Livewire::test(ActivityTimeline::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
    ]);

    $data = $component->get('timelineData');
    $entry = $data['groups'][0]['entries'][0] ?? null;

    expect($entry)->not->toBeNull()
        ->and($entry['causer_initials'])->toBeString();
});

it('counts changed fields for updated events', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Test Co']);

    Activity::withoutGlobalScopes()->where('subject_id', $company->getKey())->delete();

    Activity::withoutGlobalScopes()->insert([
        'log_name' => 'crm',
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'company',
        'subject_id' => $company->getKey(),
        'team_id' => $this->team->getKey(),
        'attribute_changes' => json_encode([
            'attributes' => ['name' => 'New', 'phone' => '555'],
            'old' => ['name' => 'Old', 'phone' => '444'],
        ]),
        'properties' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(ActivityTimeline::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
    ]);

    $data = $component->get('timelineData');
    $entry = $data['groups'][0]['entries'][0];

    expect($entry['field_count'])->toBe(2);
});

it('includes time-only format in entries', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(14)->addMinutes(30));

    $company = Company::factory()->for($this->team)->create(['name' => 'Test Co']);

    $component = Livewire::test(ActivityTimeline::class, [
        'subjectType' => 'company',
        'subjectId' => (string) $company->getKey(),
    ]);

    $data = $component->get('timelineData');
    $entry = $data['groups'][0]['entries'][0];

    expect($entry['created_at_time'])->toBe('2:30 PM');
});

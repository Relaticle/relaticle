<?php

declare(strict_types=1);

use App\Models\ActivityLog\Activity;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\CustomFieldSection;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);

    $section = CustomFieldSection::query()->create([
        'tenant_id' => $this->team->getKey(),
        'entity_type' => 'company',
        'code' => 'general',
        'name' => 'General',
        'type' => 'section',
        'sort_order' => 0,
        'active' => true,
    ]);

    $this->field = CustomField::query()->create([
        'tenant_id' => $this->team->getKey(),
        'custom_field_section_id' => $section->getKey(),
        'entity_type' => 'company',
        'code' => 'lead_source',
        'name' => 'Lead source',
        'type' => 'text',
        'sort_order' => 1,
        'active' => true,
        'validation_rules' => [],
    ]);
});

it('logs a custom_field_changes activity when a value is created', function (): void {
    $company = Company::factory()->for($this->team)->create();
    Activity::withoutGlobalScopes()->delete();

    $company->saveCustomFields(['lead_source' => 'referral']);

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('custom_field_changes')
        ->and($activity->properties['custom_field_changes'][0]['code'])->toBe('lead_source')
        ->and($activity->properties['custom_field_changes'][0]['new']['label'])->toBe('referral')
        ->and($activity->properties['custom_field_changes'][0]['old']['value'])->toBeNull();
});

it('logs a custom_field_changes activity when a value is updated', function (): void {
    $company = Company::factory()->for($this->team)->create();
    $company->saveCustomFields(['lead_source' => 'referral']);
    Activity::withoutGlobalScopes()->delete();

    $company->saveCustomFields(['lead_source' => 'linkedin']);

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('custom_field_changes')
        ->and($activity->properties['custom_field_changes'][0]['old']['label'])->toBe('referral')
        ->and($activity->properties['custom_field_changes'][0]['new']['label'])->toBe('linkedin');
});

it('does not log when saving an empty value for a previously empty field', function (): void {
    $company = Company::factory()->for($this->team)->create();
    Activity::withoutGlobalScopes()->delete();

    $company->saveCustomFields(['lead_source' => null]);

    expect(Activity::withoutGlobalScopes()->where('event', 'custom_field_changes')->count())->toBe(0);
});

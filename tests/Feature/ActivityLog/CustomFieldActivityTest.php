<?php

declare(strict_types=1);

use App\Models\ActivityLog\Activity;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('amends the latest activity when custom fields change within 5 seconds', function (): void {
    $company = Company::factory()->for($this->team)->create();

    $company->recordCustomFieldChanges([[
        'code' => 'lead_source',
        'label' => 'Lead source',
        'type' => 'text',
        'old' => ['value' => null, 'label' => null],
        'new' => ['value' => 'referral', 'label' => 'Referral'],
    ]]);

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['custom_field_changes'])->toHaveCount(1);
});

it('creates a new custom_field_changes activity when no recent activity exists', function (): void {
    $company = Company::factory()->for($this->team)->create();

    Activity::withoutGlobalScopes()->delete();

    $company->recordCustomFieldChanges([[
        'code' => 'lead_source',
        'label' => 'Lead source',
        'type' => 'text',
        'old' => ['value' => null, 'label' => null],
        'new' => ['value' => 'referral', 'label' => 'Referral'],
    ]]);

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('custom_field_changes')
        ->and($activity->properties['custom_field_changes'])->toHaveCount(1);
});

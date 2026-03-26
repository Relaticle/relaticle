<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\ActivityLog\Models\Activity;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('logs activity when a company is created', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Acme Corp']);

    $activity = Activity::withoutGlobalScopes()
        ->where('subject_type', 'company')
        ->where('subject_id', $company->getKey())
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->log_name)->toBe('crm')
        ->and($activity->team_id)->toBe($this->team->getKey())
        ->and($activity->causer_id)->toBe($this->user->getKey());
});

it('logs activity when a company is updated', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Acme Corp']);

    Activity::withoutGlobalScopes()->delete();

    $company->update(['name' => 'Acme Corporation']);

    $activity = Activity::withoutGlobalScopes()
        ->where('subject_type', 'company')
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull();

    $changes = $activity->attribute_changes;
    expect($changes['old']['name'])->toBe('Acme Corp')
        ->and($changes['attributes']['name'])->toBe('Acme Corporation');
});

it('logs activity when a company is deleted', function (): void {
    $company = Company::factory()->for($this->team)->create();

    Activity::withoutGlobalScopes()->delete();

    $company->delete();

    $activity = Activity::withoutGlobalScopes()
        ->where('subject_type', 'company')
        ->where('event', 'deleted')
        ->first();

    expect($activity)->not->toBeNull();
});

it('does not log noise attributes', function (): void {
    $company = Company::factory()->for($this->team)->create();

    $activity = Activity::withoutGlobalScopes()
        ->where('subject_type', 'company')
        ->where('event', 'created')
        ->first();

    $properties = $activity->properties->toArray();
    $attributeKeys = array_keys($properties['attributes'] ?? []);

    expect($attributeKeys)->not->toContain('id')
        ->and($attributeKeys)->not->toContain('team_id')
        ->and($attributeKeys)->not->toContain('creator_id')
        ->and($attributeKeys)->not->toContain('created_at')
        ->and($attributeKeys)->not->toContain('updated_at')
        ->and($attributeKeys)->not->toContain('creation_source');
});

it('logs activity for all CRM models', function (string $modelClass, array $factoryArgs): void {
    $record = $modelClass::factory()->for($this->team)->create($factoryArgs);

    $morphAlias = array_search($modelClass, Relation::morphMap(), true);

    $activity = Activity::withoutGlobalScopes()
        ->where('subject_type', $morphAlias)
        ->where('subject_id', $record->getKey())
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->team_id)->toBe($this->team->getKey());
})->with([
    'Company' => [Company::class, ['name' => 'Test Co']],
    'People' => [People::class, ['name' => 'John Doe']],
    'Opportunity' => [Opportunity::class, []],
    'Task' => [Task::class, ['title' => 'Test Task']],
    'Note' => [Note::class, []],
]);

it('does not log empty changes', function (): void {
    $company = Company::factory()->for($this->team)->create(['name' => 'Test']);

    Activity::withoutGlobalScopes()->delete();

    $company->update(['name' => 'Test']);

    expect(Activity::withoutGlobalScopes()->count())->toBe(0);
});

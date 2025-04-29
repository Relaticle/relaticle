<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('company belongs to team', function () {
    $team = Team::factory()->create();
    $company = Company::factory()->create([
        'team_id' => $team->id,
    ]);

    expect($company->team)->toBeInstanceOf(Team::class)
        ->and($company->team->id)->toBe($team->id);
});

test('company belongs to creator', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'creator_id' => $user->id,
    ]);

    expect($company->creator)->toBeInstanceOf(User::class)
        ->and($company->creator->id)->toBe($user->id);
})->todo();

test('company belongs to account owner', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create([
        'account_owner_id' => $user->id,
    ]);

    expect($company->accountOwner)->toBeInstanceOf(User::class)
        ->and($company->accountOwner->id)->toBe($user->id);
})->todo();

test('company has many people', function () {
    $company = Company::factory()->create();
    $people = People::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($company->people->first())->toBeInstanceOf(People::class)
        ->and($company->people->first()->id)->toBe($people->id);
})->todo();

test('company has many opportunities', function () {
    $company = Company::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'company_id' => $company->id,
    ]);

    expect($company->opportunities->first())->toBeInstanceOf(Opportunity::class)
        ->and($company->opportunities->first()->id)->toBe($opportunity->id);
})->todo();

test('company morph to many tasks', function () {
    $company = Company::factory()->create();
    $task = Task::factory()->create();

    $company->tasks()->attach($task);

    expect($company->tasks->first())->toBeInstanceOf(Task::class)
        ->and($company->tasks->first()->id)->toBe($task->id);
})->todo();

test('company morph to many notes', function () {
    $company = Company::factory()->create();
    $note = Note::factory()->create();

    $company->notes()->attach($note);

    expect($company->notes->first())->toBeInstanceOf(Note::class)
        ->and($company->notes->first()->id)->toBe($note->id);
})->todo();

test('company has logo attribute', function () {
    $company = Company::factory()->create([
        'name' => 'Test Company',
    ]);

    expect($company->logo)->not->toBeNull();
})->todo();

test('company uses media library', function () {
    $company = Company::factory()->create();

    expect(class_implements($company))->toContain(\Spatie\MediaLibrary\HasMedia::class)
        ->and(class_uses_recursive($company))->toContain(\Spatie\MediaLibrary\InteractsWithMedia::class);
})->todo();

test('company uses custom fields', function () {
    $company = Company::factory()->create();

    expect(class_implements($company))->toContain(\Relaticle\CustomFields\Models\Contracts\HasCustomFields::class)
        ->and(class_uses_recursive($company))->toContain(\Relaticle\CustomFields\Models\Concerns\UsesCustomFields::class);
})->todo();

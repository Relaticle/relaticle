<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\UpdateRecordAction;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

it('updates a record from trigger context', function () {
    $company = TestCompany::create(['name' => 'Old Name']);

    $action = new UpdateRecordAction();

    $result = $action->execute([
        'record_source' => 'trigger',
        'field_mappings' => ['name' => 'New Name'],
    ], [
        'trigger' => ['record' => $company],
    ]);

    expect($result['updated'])->toBeTrue();
    expect($result['id'])->toBe($company->id);

    $company->refresh();
    expect($company->name)->toBe('New Name');
});

it('returns error when record cannot be resolved', function () {
    $action = new UpdateRecordAction();

    $result = $action->execute([
        'record_source' => 'trigger',
        'field_mappings' => ['name' => 'Test'],
    ], [
        'trigger' => [],
    ]);

    expect($result['updated'])->toBeFalse();
    expect($result['error'])->toContain('Could not resolve record');
});

it('returns error when step source has no valid output', function () {
    $action = new UpdateRecordAction();

    $result = $action->execute([
        'record_source' => 'step',
        'step_node_id' => 'action-2',
        'field_mappings' => ['name' => 'Test'],
    ], [
        'steps' => [],
    ]);

    expect($result['updated'])->toBeFalse();
});

<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\DeleteRecordAction;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

it('deletes a record from trigger context', function () {
    $company = TestCompany::create(['name' => 'To Delete']);

    $action = new DeleteRecordAction();

    $result = $action->execute([
        'record_source' => 'trigger',
    ], [
        'trigger' => ['record' => $company],
    ]);

    expect($result['deleted'])->toBeTrue();
    expect($result['id'])->toBe($company->id);

    // Record should be deleted (TestCompany doesn't use SoftDeletes)
    expect(TestCompany::find($company->id))->toBeNull();
});

it('returns error when record cannot be resolved', function () {
    $action = new DeleteRecordAction();

    $result = $action->execute([
        'record_source' => 'trigger',
    ], [
        'trigger' => [],
    ]);

    expect($result['deleted'])->toBeFalse();
    expect($result['error'])->toContain('Could not resolve record');
});

it('returns error when step source has no valid output', function () {
    $action = new DeleteRecordAction();

    $result = $action->execute([
        'record_source' => 'step',
        'step_node_id' => 'action-3',
    ], [
        'steps' => [],
    ]);

    expect($result['deleted'])->toBeFalse();
});

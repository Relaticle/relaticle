<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\CreateRecordAction;
use Relaticle\Workflow\Schema\EntityDefinition;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

beforeEach(function () {
    // Mock RelaticleSchema to use test fixtures
    $schema = Mockery::mock(RelaticleSchema::class);
    $schema->shouldReceive('getEntity')
        ->with('companies')
        ->andReturn(new EntityDefinition('companies', 'Companies', TestCompany::class, 'test_companies'));
    $schema->shouldReceive('getEntity')
        ->with(Mockery::on(fn ($v) => $v !== 'companies'))
        ->andReturn(null);

    app()->instance(RelaticleSchema::class, $schema);
});

it('creates a record with field mappings', function () {
    $action = new CreateRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'field_mappings' => ['name' => 'Acme Corp', 'domain' => 'acme.com'],
    ], []);

    expect($result['created'])->toBeTrue();
    expect($result['id'])->not->toBeNull();

    $company = TestCompany::find($result['id']);
    expect($company)->not->toBeNull();
    expect($company->name)->toBe('Acme Corp');
    expect($company->domain)->toBe('acme.com');
});

it('sets tenant_id from workflow context', function () {
    $workflow = new \stdClass();
    $workflow->tenant_id = 'tenant-123';
    $workflow->creator_id = 'user-456';

    $action = new CreateRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'field_mappings' => ['name' => 'Tenant Test Co'],
    ], ['_workflow' => $workflow]);

    expect($result['created'])->toBeTrue();

    $company = TestCompany::find($result['id']);
    expect($company->tenant_id)->toBe('tenant-123');
});

it('returns error for missing entity type', function () {
    $action = new CreateRecordAction();

    $result = $action->execute([
        'field_mappings' => ['name' => 'Test'],
    ], []);

    expect($result['created'])->toBeFalse();
    expect($result['error'])->toContain('entity_type is required');
});

it('returns error for unknown entity type', function () {
    $action = new CreateRecordAction();

    $result = $action->execute([
        'entity_type' => 'unknown',
        'field_mappings' => ['name' => 'Test'],
    ], []);

    expect($result['created'])->toBeFalse();
    expect($result['error'])->toContain('Unknown entity type');
});

it('returns record data in output', function () {
    $action = new CreateRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'field_mappings' => ['name' => 'Output Test'],
    ], []);

    expect($result['record'])->toBeArray();
    expect($result['record']['name'])->toBe('Output Test');
});

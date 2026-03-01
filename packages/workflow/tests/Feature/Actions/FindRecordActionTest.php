<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\FindRecordAction;
use Relaticle\Workflow\Schema\EntityDefinition;
use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\Tests\Fixtures\TestCompany;

beforeEach(function () {
    $schema = Mockery::mock(RelaticleSchema::class);
    $schema->shouldReceive('getEntity')
        ->with('companies')
        ->andReturn(new EntityDefinition('companies', 'Companies', TestCompany::class, 'test_companies'));
    $schema->shouldReceive('getEntity')
        ->with(Mockery::on(fn ($v) => $v !== 'companies'))
        ->andReturn(null);

    app()->instance(RelaticleSchema::class, $schema);
});

it('finds a record by field condition', function () {
    TestCompany::create(['name' => 'Acme Corp', 'domain' => 'acme.com']);
    TestCompany::create(['name' => 'Other Corp', 'domain' => 'other.com']);

    $action = new FindRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'conditions' => [
            ['field' => 'name', 'operator' => 'equals', 'value' => 'Acme Corp'],
        ],
    ], []);

    expect($result['found'])->toBeTrue();
    expect($result['record']['name'])->toBe('Acme Corp');
});

it('finds records with contains operator', function () {
    TestCompany::create(['name' => 'Acme Corporation']);

    $action = new FindRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'conditions' => [
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Acme'],
        ],
    ], []);

    expect($result['found'])->toBeTrue();
    expect($result['record']['name'])->toBe('Acme Corporation');
});

it('returns found false when no match', function () {
    TestCompany::create(['name' => 'Acme Corp']);

    $action = new FindRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'conditions' => [
            ['field' => 'name', 'operator' => 'equals', 'value' => 'Nonexistent'],
        ],
    ], []);

    expect($result['found'])->toBeFalse();
});

it('returns error for missing entity type', function () {
    $action = new FindRecordAction();

    $result = $action->execute([
        'conditions' => [],
    ], []);

    expect($result['found'])->toBeFalse();
    expect($result['error'])->toContain('entity_type is required');
});

it('scopes to workflow tenant', function () {
    TestCompany::create(['name' => 'My Co', 'tenant_id' => 'tenant-1']);
    TestCompany::create(['name' => 'Other Co', 'tenant_id' => 'tenant-2']);

    $workflow = new \stdClass();
    $workflow->tenant_id = 'tenant-1';

    $action = new FindRecordAction();

    $result = $action->execute([
        'entity_type' => 'companies',
        'conditions' => [
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Co'],
        ],
    ], ['_workflow' => $workflow]);

    expect($result['found'])->toBeTrue();
    expect($result['record']['name'])->toBe('My Co');
});

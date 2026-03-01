<?php

declare(strict_types=1);

use Relaticle\Workflow\Schema\EntityDefinition;
use Relaticle\Workflow\Schema\FieldDefinition;
use Relaticle\Workflow\Schema\RelaticleSchema;

it('returns all five entities', function () {
    $schema = new RelaticleSchema();
    $entities = $schema->getEntities();

    expect($entities)->toHaveCount(5);
    expect(array_keys($entities))->toBe(['people', 'companies', 'opportunities', 'tasks', 'notes']);

    foreach ($entities as $entity) {
        expect($entity)->toBeInstanceOf(EntityDefinition::class);
        expect($entity->key)->toBeString()->not->toBeEmpty();
        expect($entity->label)->toBeString()->not->toBeEmpty();
        expect($entity->modelClass)->toBeString()->not->toBeEmpty();
        expect($entity->tableName)->toBeString()->not->toBeEmpty();
    }
});

it('returns entity by key', function () {
    $schema = new RelaticleSchema();

    $people = $schema->getEntity('people');
    expect($people)->not->toBeNull();
    expect($people->key)->toBe('people');
    expect($people->label)->toBe('People');

    $companies = $schema->getEntity('companies');
    expect($companies)->not->toBeNull();
    expect($companies->key)->toBe('companies');
    expect($companies->label)->toBe('Companies');
});

it('returns null for unknown entity key', function () {
    $schema = new RelaticleSchema();

    expect($schema->getEntity('unknown'))->toBeNull();
});

it('returns standard fields for people', function () {
    $schema = new RelaticleSchema();
    $fields = $schema->getFields('people');

    $fieldKeys = array_map(fn (FieldDefinition $f) => $f->key, $fields);

    expect($fieldKeys)->toContain('name');

    $nameField = collect($fields)->firstWhere('key', 'name');
    expect($nameField)->not->toBeNull();
    expect($nameField->type)->toBe('string');
    expect($nameField->isCustomField)->toBeFalse();
    expect($nameField->customFieldId)->toBeNull();
});

it('returns standard fields for companies', function () {
    $schema = new RelaticleSchema();
    $fields = $schema->getFields('companies');

    $fieldKeys = array_map(fn (FieldDefinition $f) => $f->key, $fields);

    expect($fieldKeys)->toContain('name');
    expect($fieldKeys)->toContain('address');
    expect($fieldKeys)->toContain('country');
    expect($fieldKeys)->toContain('phone');
});

it('returns relationship foreign keys as fields', function () {
    $schema = new RelaticleSchema();
    $peopleFields = $schema->getFields('people');
    $fieldKeys = array_map(fn (FieldDefinition $f) => $f->key, $peopleFields);

    expect($fieldKeys)->toContain('company_id');

    $companyField = collect($peopleFields)->firstWhere('key', 'company_id');
    expect($companyField->type)->toBe('relation');
    expect($companyField->isCustomField)->toBeFalse();
});

it('returns relationships for people', function () {
    $schema = new RelaticleSchema();
    $relationships = $schema->getRelationships('people');

    expect($relationships)->toHaveKey('company');
    expect($relationships['company']['related_entity'])->toBe('companies');
    expect($relationships['company']['type'])->toBe('belongs_to');
});

it('returns relationships for opportunities', function () {
    $schema = new RelaticleSchema();
    $relationships = $schema->getRelationships('opportunities');

    expect($relationships)->toHaveKey('company');
    expect($relationships)->toHaveKey('contact');
    expect($relationships['contact']['related_entity'])->toBe('people');
});

it('returns empty relationships for entities without relationships', function () {
    $schema = new RelaticleSchema();

    expect($schema->getRelationships('companies'))->toBeEmpty();
    expect($schema->getRelationships('tasks'))->toBeEmpty();
    expect($schema->getRelationships('notes'))->toBeEmpty();
});

it('returns empty fields for unknown entity', function () {
    $schema = new RelaticleSchema();

    expect($schema->getFields('unknown'))->toBeEmpty();
});

it('creates field definitions with correct structure', function () {
    $field = new FieldDefinition(
        key: 'test_field',
        label: 'Test Field',
        type: 'string',
        isCustomField: true,
        customFieldId: '01HXYZ123',
        options: [['value' => '1', 'label' => 'Option 1']],
        required: true,
    );

    expect($field->key)->toBe('test_field');
    expect($field->label)->toBe('Test Field');
    expect($field->type)->toBe('string');
    expect($field->isCustomField)->toBeTrue();
    expect($field->customFieldId)->toBe('01HXYZ123');
    expect($field->options)->toHaveCount(1);
    expect($field->required)->toBeTrue();
});

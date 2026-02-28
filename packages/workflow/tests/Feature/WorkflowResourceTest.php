<?php

declare(strict_types=1);

use Relaticle\Workflow\Filament\Resources\WorkflowResource;
use Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages\CreateWorkflow;
use Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages\EditWorkflow;
use Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages\ListWorkflows;
use Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages\ViewWorkflow;
use Relaticle\Workflow\Filament\WorkflowPlugin;
use Relaticle\Workflow\Models\Workflow;

it('can instantiate the WorkflowPlugin', function () {
    $plugin = WorkflowPlugin::make();

    expect($plugin)->toBeInstanceOf(WorkflowPlugin::class);
    expect($plugin->getId())->toBe('workflow');
});

it('has the correct resource model', function () {
    expect(WorkflowResource::getModel())->toBe(Workflow::class);
});

it('has the correct navigation icon', function () {
    $reflection = new ReflectionClass(WorkflowResource::class);
    $property = $reflection->getProperty('navigationIcon');
    expect($property->getDefaultValue())->toBe('heroicon-o-bolt');
});

it('has the correct navigation group', function () {
    $reflection = new ReflectionClass(WorkflowResource::class);
    $property = $reflection->getProperty('navigationGroup');
    expect($property->getDefaultValue())->toBe('Automation');
});

it('registers the correct pages', function () {
    $pages = WorkflowResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'view', 'edit']);
});

it('has the correct page classes', function () {
    $pages = WorkflowResource::getPages();

    expect($pages['index']->getPage())->toBe(ListWorkflows::class);
    expect($pages['create']->getPage())->toBe(CreateWorkflow::class);
    expect($pages['edit']->getPage())->toBe(EditWorkflow::class);
});

it('has form components for workflow creation', function () {
    expect(method_exists(WorkflowResource::class, 'form'))->toBeTrue();
});

it('has table columns for workflow listing', function () {
    expect(method_exists(WorkflowResource::class, 'table'))->toBeTrue();
});

it('page classes reference the correct resource', function () {
    $listReflection = new ReflectionClass(ListWorkflows::class);
    $listResource = $listReflection->getProperty('resource');
    expect($listResource->getDefaultValue())->toBe(WorkflowResource::class);

    $createReflection = new ReflectionClass(CreateWorkflow::class);
    $createResource = $createReflection->getProperty('resource');
    expect($createResource->getDefaultValue())->toBe(WorkflowResource::class);

    $editReflection = new ReflectionClass(EditWorkflow::class);
    $editResource = $editReflection->getProperty('resource');
    expect($editResource->getDefaultValue())->toBe(WorkflowResource::class);
});

it('has a view page', function () {
    $pages = \Relaticle\Workflow\Filament\Resources\WorkflowResource::getPages();
    expect($pages)->toHaveKey('view');
});

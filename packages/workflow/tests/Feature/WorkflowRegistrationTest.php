<?php

declare(strict_types=1);

use Relaticle\Workflow\Actions\Contracts\WorkflowAction;
use Relaticle\Workflow\Facades\Workflow;

beforeEach(function () {
    // Reset the manager state between tests
    app()->forgetInstance(\Relaticle\Workflow\WorkflowManager::class);
    app()->singleton(\Relaticle\Workflow\WorkflowManager::class);
});

it('registers a triggerable model with events and fields', function () {
    Workflow::registerTriggerableModel('App\\Models\\Company', [
        'label' => 'Company',
        'events' => ['created', 'updated', 'deleted'],
        'fields' => fn () => [
            'name' => ['type' => 'string', 'label' => 'Name'],
        ],
    ]);

    $models = Workflow::getTriggerableModels();

    expect($models)->toHaveKey('App\\Models\\Company');
    expect($models['App\\Models\\Company']['label'])->toBe('Company');
    expect($models['App\\Models\\Company']['events'])->toContain('created');
});

it('registers a custom action class', function () {
    $action = new class implements WorkflowAction {
        public function execute(array $config, array $context): array
        {
            return ['done' => true];
        }

        public static function label(): string
        {
            return 'Test Action';
        }

        public static function configSchema(): array
        {
            return [];
        }
    };

    Workflow::registerAction('test_action', $action::class);

    $actions = Workflow::getRegisteredActions();

    expect($actions)->toHaveKey('test_action');
});

it('rejects action classes not implementing WorkflowAction', function () {
    Workflow::registerAction('bad_action', \stdClass::class);
})->throws(\InvalidArgumentException::class);

it('configures tenancy scoping', function () {
    Workflow::useTenancy(
        scopeColumn: 'team_id',
        resolver: fn () => 'team-123',
    );

    $config = Workflow::getTenancyConfig();

    expect($config['scopeColumn'])->toBe('team_id');
    expect(($config['resolver'])())->toBe('team-123');
});

it('lists all registered models and actions', function () {
    Workflow::registerTriggerableModel('App\\Models\\Task', [
        'label' => 'Task',
        'events' => ['created'],
        'fields' => fn () => [],
    ]);

    $actionClass = get_class(new class implements WorkflowAction {
        public function execute(array $config, array $context): array { return []; }
        public static function label(): string { return 'Send Webhook'; }
        public static function configSchema(): array { return []; }
    });

    Workflow::registerAction('send_webhook', $actionClass);

    expect(Workflow::getTriggerableModels())->not->toBeEmpty();
    expect(Workflow::getRegisteredActions())->not->toBeEmpty();
});

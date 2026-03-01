<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use Illuminate\Support\ServiceProvider;
use Relaticle\Workflow\Facades\Workflow;

class WorkflowServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerTriggerableModels();
        $this->configureTenancy();
    }

    private function registerTriggerableModels(): void
    {
        Workflow::registerTriggerableModel(Company::class, [
            'label' => 'Company',
            'events' => ['created', 'updated', 'deleted'],
            'fields' => fn () => [
                'name' => ['type' => 'string', 'label' => 'Name'],
                'creation_source' => ['type' => 'string', 'label' => 'Creation Source'],
            ],
        ]);

        Workflow::registerTriggerableModel(People::class, [
            'label' => 'Person',
            'events' => ['created', 'updated', 'deleted'],
            'fields' => fn () => [
                'name' => ['type' => 'string', 'label' => 'Name'],
                'company_id' => ['type' => 'string', 'label' => 'Company'],
                'creation_source' => ['type' => 'string', 'label' => 'Creation Source'],
            ],
        ]);

        Workflow::registerTriggerableModel(Opportunity::class, [
            'label' => 'Opportunity',
            'events' => ['created', 'updated', 'deleted'],
            'fields' => fn () => [
                'name' => ['type' => 'string', 'label' => 'Name'],
                'company_id' => ['type' => 'string', 'label' => 'Company'],
                'contact_id' => ['type' => 'string', 'label' => 'Contact'],
                'creation_source' => ['type' => 'string', 'label' => 'Creation Source'],
            ],
        ]);

        Workflow::registerTriggerableModel(Task::class, [
            'label' => 'Task',
            'events' => ['created', 'updated', 'deleted'],
            'fields' => fn () => [
                'title' => ['type' => 'string', 'label' => 'Title'],
                'creation_source' => ['type' => 'string', 'label' => 'Creation Source'],
            ],
        ]);

        Workflow::registerTriggerableModel(Note::class, [
            'label' => 'Note',
            'events' => ['created', 'updated', 'deleted'],
            'fields' => fn () => [
                'title' => ['type' => 'string', 'label' => 'Title'],
                'creation_source' => ['type' => 'string', 'label' => 'Creation Source'],
            ],
        ]);
    }

    private function configureTenancy(): void
    {
        Workflow::useTenancy(
            scopeColumn: 'tenant_id',
            resolver: fn () => filament()->getTenant()?->id,
        );
    }
}

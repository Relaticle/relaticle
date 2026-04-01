<?php

declare(strict_types=1);

namespace App\Services;

use Filament\Facades\Filament;

final readonly class ChatContextService
{
    /**
     * @return array{page: string|null, record_type: string|null, record_id: string|null, record_name: string|null}
     */
    public function getContext(): array
    {
        $currentPanel = Filament::getCurrentPanel();

        return [
            'page' => request()->route()?->getName(),
            'record_type' => null,
            'record_id' => null,
            'record_name' => null,
        ];
    }

    /**
     * @param  array{page: string|null, record_type: string|null, record_id: string|null, record_name: string|null}  $context
     * @return array<int, array{label: string, prompt: string}>
     */
    public function getSuggestedPrompts(array $context): array
    {
        $prompts = [
            ['label' => 'CRM overview', 'prompt' => 'Give me a summary of my CRM data'],
            ['label' => 'Overdue tasks', 'prompt' => 'Show my overdue tasks'],
            ['label' => 'Recent companies', 'prompt' => 'List companies added this week'],
            ['label' => 'Pipeline summary', 'prompt' => 'Show my opportunity pipeline summary'],
        ];

        if ($context['record_type'] === 'company' && $context['record_name']) {
            array_unshift($prompts,
                ['label' => "Summarize {$context['record_name']}", 'prompt' => "Summarize the company {$context['record_name']}"],
                ['label' => 'Find contacts', 'prompt' => "Find contacts at {$context['record_name']}"],
            );
        }

        if ($context['record_type'] === 'task') {
            array_unshift($prompts,
                ['label' => 'My tasks', 'prompt' => 'Show all my assigned tasks'],
            );
        }

        return array_slice($prompts, 0, 6);
    }
}

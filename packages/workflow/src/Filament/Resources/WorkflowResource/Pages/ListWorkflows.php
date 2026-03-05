<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Relaticle\Workflow\Filament\Resources\WorkflowResource;
use Relaticle\Workflow\Models\WorkflowTemplate;
use Relaticle\Workflow\Services\WorkflowTemplateService;

class ListWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('browse_templates')
                ->label('Browse Templates')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->modalHeading('Workflow Templates')
                ->modalDescription('Start from a pre-built template — then customise it in the builder.')
                ->modalWidth('2xl')
                ->form([
                    Select::make('template_id')
                        ->label('Template')
                        ->options(function () {
                            return WorkflowTemplate::active()
                                ->orderBy('category')
                                ->orderBy('name')
                                ->get()
                                ->groupBy('category')
                                ->map(fn ($group) => $group->pluck('name', 'id'))
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->helperText('Select a template to use as your starting point.'),
                    TextInput::make('name')
                        ->label('Workflow Name')
                        ->placeholder('Leave blank to use the template name')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->instantiateTemplate($data['template_id'], $data['name'] ?? null);
                }),

            CreateAction::make(),
        ];
    }

    public function createFromTemplate(string $templateId): void
    {
        $this->instantiateTemplate($templateId);
    }

    protected function instantiateTemplate(string $templateId, ?string $name = null): void
    {
        /** @var WorkflowTemplate|null $template */
        $template = WorkflowTemplate::find($templateId);
        if ($template === null) {
            return;
        }

        $user = auth()->user();
        $tenantId = $user?->currentTeam?->id ?? $user?->team_id ?? null;

        if ($tenantId === null) {
            Notification::make()
                ->title('No team context found.')
                ->danger()
                ->send();

            return;
        }

        $overrides = [];
        if (filled($name)) {
            $overrides['name'] = $name;
        }

        /** @var WorkflowTemplateService $service */
        $service = app(WorkflowTemplateService::class);
        $workflow = $service->createFromTemplate(
            $template,
            (string) $tenantId,
            auth()->id() ? (string) auth()->id() : null,
            $overrides,
        );

        Notification::make()
            ->title('Workflow created from template!')
            ->success()
            ->send();

        $this->redirect(WorkflowResource::getUrl('builder', ['record' => $workflow]));
    }
}

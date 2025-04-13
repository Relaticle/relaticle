<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Adapters\OpportunitiesKanbanAdapter;
use App\Filament\App\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Models\Opportunity;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

final class OpportunitiesBoard extends KanbanBoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Opportunities';

    protected static ?string $navigationParentItem = 'Opportunities';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public function getSubject(): Builder
    {
        return Opportunity::query();
    }

    public function mount(): void
    {
        $this->titleField('name')
            ->columnField('stage')
            ->descriptionField('description')
            ->orderField('order_column')
            ->columns($this->stages()->pluck('name', 'id')->toArray())
            ->columnColors()
            ->cardLabel('Opportunity');
    }

    public function createAction(Action $action): Action
    {
        return $action
            ->iconButton()
            ->icon('heroicon-o-plus')
            ->slideOver(false)
            ->label('Create Opportunity')
            ->modalWidth('2xl')
            ->form(fn (Forms\Form $form): \Filament\Forms\Form => OpportunityForm::get($form))
            ->action(function (Action $action, array $arguments): void {
                $opportunity = Auth::user()->currentTeam->opportunities()->create($action->getFormData());
                $opportunity->saveCustomFieldValue($this->stageCustomField(), $arguments['column']);
            });
    }

    public function editAction(Action $action): Action
    {
        return $action->form(fn (Forms\Form $form): \Filament\Forms\Form => OpportunityForm::get($form));
    }

    public function getAdapter(): KanbanAdapterInterface
    {
        return new OpportunitiesKanbanAdapter(Opportunity::query(), $this->config);
    }

    private function stageCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', 'stage')
            ->firstOrFail();
    }

    private function stages(): Collection
    {
        return $this->stageCustomField()->options->map(fn ($option): array => [
            'id' => $option->id,
            'custom_field_id' => $option->custom_field_id,
            'name' => $option->name,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Adapters\OpportunitiesKanbanAdapter;
use App\Filament\App\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Models\Opportunity;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

final class OpportunitiesBoard extends KanbanBoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Opportunities';

    protected static ?string $navigationParentItem = 'Opportunities';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    /**
     * @return Builder<Opportunity>
     */
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
            ->cardLabel('Opportunity')
            ->cardAttributes([
                'company.name' => '',
                'contact.name' => '',
            ])
            ->cardAttributeColors([
                'company.name' => 'white',
                'contact.name' => 'white',
            ])
            ->cardAttributeIcons([
                'contact.name' => 'heroicon-o-user',
                'company.name' => 'heroicon-o-building-office',
            ]);
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
                /** @var Team $currentTeam */
                $currentTeam = Auth::user()->currentTeam;
                /** @var Opportunity $opportunity */
                $opportunity = $currentTeam->opportunities()->create($action->getFormData());
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

    private function stageCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', 'stage')
            ->firstOrFail();
    }

    /**
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed}>
     */
    private function stages(): Collection
    {
        return $this->stageCustomField()->options->map(fn (CustomFieldOption $option): array => [
            'id' => $option->getKey(),
            'custom_field_id' => $option->getAttribute('custom_field_id'),
            'name' => $option->getAttribute('name'),
        ]);
    }

    public static function canAccess(): bool
    {
        return (new self)->stageCustomField() instanceof CustomField;
    }
}

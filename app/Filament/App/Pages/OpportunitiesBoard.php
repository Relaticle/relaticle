<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\OpportunityResource;
use App\Models\Opportunity;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;
use App\Filament\App\Adapters\OpportunitiesKanbanAdapter;

final class OpportunitiesBoard extends KanbanBoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Opportunities';

    protected static ?string $navigationParentItem = 'Opportunities';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    /**
     * @return void
     */
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

    /**
     * @return KanbanAdapterInterface
     */
    public function getAdapter(): KanbanAdapterInterface
    {
        return new OpportunitiesKanbanAdapter(Opportunity::query(), $this->config);
    }

    /**
     * @return CustomField
     */
    protected function stageCustomField(): CustomField
    {
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', 'stage')
            ->firstOrFail();
    }

    /**
     * @return Collection
     */
    protected function stages(): Collection
    {
        return $this->stageCustomField()->options->map(fn($option): array => [
            'id' => $option->id,
            'custom_field_id' => $option->custom_field_id,
            'name' => $option->name,
        ]);
    }
}

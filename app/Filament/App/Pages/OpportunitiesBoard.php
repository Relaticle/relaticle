<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Opportunity;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

final class OpportunitiesBoard extends AbstractKanbanBoard implements HasForms
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Opportunities';

    protected static ?string $navigationParentItem = 'Opportunities';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected function getModelClass(): string
    {
        return Opportunity::class;
    }

    public function getTitleAttribute(): string
    {
        return 'name';
    }

    protected function getStatusFieldCode(): string
    {
        return 'stage';
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->placeholder('Enter opportunity title')
                ->columnSpanFull(),
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('contact_id')
                ->relationship('contact', 'name')
                ->preload(),
            CustomFieldsComponent::make()->model($this->getModelClass()),
        ];
    }

    /**
     * Get the default form data for a new record
     */
    public function getDefaultFormData(array $status): array
    {
        return [
            'custom_fields' => [
                $this->getStatusFieldCode() => $status['id'],
            ],
        ];
    }

    /**
     * Create a new record with the given data
     */
    public function createRecord(array $data): Model
    {
        return auth()->user()->currentTeam->opportunities()->create($data);
    }

    public function updateRecord(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CustomFields\Opportunity as OpportunityCustomField;
use App\Filament\Resources\OpportunityResource\Forms\OpportunityForm;
use App\Models\Opportunity;
use App\Models\Team;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Exception\InvalidArgumentException;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Components\CardFlex;
use Throwable;
use UnitEnum;

final class OpportunitiesBoard extends BoardPage
{
    protected static ?string $navigationLabel = 'Board';

    protected static ?string $title = 'Opportunities';

    protected static ?string $navigationParentItem = 'Opportunities';

    protected static string|null|UnitEnum $navigationGroup = 'Workspace';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    /**
     * Configure the board using the new Filament V4 architecture.
     */
    public function board(Board $board): Board
    {
        return $board
            ->query(
                Opportunity::query()
                    ->leftJoin('custom_field_values as cfv', function ($join): void {
                        $join->on('opportunities.id', '=', 'cfv.entity_id')
                            ->where('cfv.custom_field_id', '=', $this->stageCustomField()->getKey());
                    })
                    ->select('opportunities.*', 'cfv.integer_value')
                    ->with(['company', 'contact'])
            )
            ->recordTitleAttribute('name')
            ->columnIdentifier('cfv.integer_value')
            ->positionIdentifier('order_column')
            ->searchable(['name'])
            ->columns($this->getColumns())
            ->cardSchema(fn(Schema $schema): \Filament\Schemas\Schema => $schema->components([
                CustomFields::infolist()
                    ->forSchema($schema)
                    ->only(['description'])
                    ->hiddenLabels()
                    ->visibleWhenFilled()
                    ->withoutSections()
                    ->values()
                    ->first()
                    ?->columnSpanFull()
                    ->visible(fn ($state) => filled($state))
                    ->formatStateUsing(fn (string $state): string => str($state)->stripTags()->limit()->toString()),
                CardFlex::make([

                ]),
            ]))
            ->columnActions([
                CreateAction::make()
                    ->label('Add Opportunity')
                    ->icon('heroicon-o-plus')
                    ->iconButton()
                    ->modalWidth(Width::Large)
                    ->slideOver(false)
                    ->model(Opportunity::class)
                    ->schema(fn (Schema $schema): \Filament\Schemas\Schema => OpportunityForm::get($schema))
                    ->using(function (array $data, array $arguments): Opportunity {
                        /** @var Team $currentTeam */
                        $currentTeam = Auth::user()->currentTeam;

                        /** @var Opportunity $opportunity */
                        $opportunity = $currentTeam->opportunities()->create($data);

                        $stageField = $this->stageCustomField();
                        $opportunity->saveCustomFieldValue($stageField, $arguments['column']);
                        $opportunity->order_column = $this->getBoardPositionInColumn((string) $arguments['column']);

                        return $opportunity;
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label('Edit')
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge)
                    ->icon('heroicon-o-pencil-square')
                    ->schema(fn (Schema $schema): \Filament\Schemas\Schema => OpportunityForm::get($schema))
                    ->fillForm(fn (Opportunity $record): array => [
                        'name' => $record->name,
                        'company_id' => $record->company_id,
                        'contact_id' => $record->contact_id,
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $record->update($data);
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Opportunity $record): void {
                        $record->delete();
                    }),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->multiple(),
                SelectFilter::make('contacts')
                    ->label('Contact')
                    ->relationship('contact', 'name')
                    ->multiple(),
            ])
            ->filtersFormWidth(Width::Medium);
    }

    /**
     * Move card to new position using Rank-based positioning.
     *
     * @throws Throwable
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null
    ): void {
        $board = $this->getBoard();
        $query = $board->getQuery();

        if (!$query instanceof \Illuminate\Database\Eloquent\Builder) {
            throw new InvalidArgumentException('Board query not available');
        }

        $card = (clone $query)->find($cardId);
        if (! $card) {
            throw new InvalidArgumentException("Card not found: {$cardId}");
        }

        // Calculate new position using Rank service
        $newPosition = $this->calculatePositionBetweenCards($afterCardId, $beforeCardId, $targetColumnId);

        // Use transaction for data consistency
        DB::transaction(function () use ($card, $board, $targetColumnId, $newPosition): void {
            $columnIdentifier = $board->getColumnIdentifierAttribute();
            $columnValue = $this->resolveStatusValue($card, $columnIdentifier, $targetColumnId);
            $positionIdentifier = $board->getPositionIdentifierAttribute();

            $card->update([$positionIdentifier => $newPosition]);

            /** @var Opportunity $card */
            $card->saveCustomFieldValue($this->stageCustomField(), $columnValue);
        });

        // Emit success event after successful transaction
        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    /**
     * Get columns for the board.
     *
     * @return array<Column>
     *
     * @throws Exception
     */
    private function getColumns(): array
    {
        return $this->stages()->map(fn (array $stage): \Relaticle\Flowforge\Column => Column::make((string) $stage['id'])
            ->color($stage['color'])
            ->label($stage['name'])
        )->toArray();
    }

    private function stageCustomField(): ?CustomField
    {
        /** @var CustomField|null */
        return CustomField::query()
            ->forEntity(Opportunity::class)
            ->where('code', OpportunityCustomField::STAGE)
            ->first();
    }

    /**
     * @return Collection<int, array{id: mixed, custom_field_id: mixed, name: mixed, color: string}>
     */
    private function stages(): Collection
    {
        $field = $this->stageCustomField();
        if (!$field instanceof \Relaticle\CustomFields\Models\CustomField) {
            return collect();
        }

        return $field->options->map(fn (CustomFieldOption $option): array => [
            'id' => $option->getKey(),
            'custom_field_id' => $option->getAttribute('custom_field_id'),
            'name' => $option->getAttribute('name'),
            'color' => $option->settings->color ?? 'gray',
        ]);
    }

    public static function canAccess(): bool
    {
        return (new self)->stageCustomField() instanceof CustomField;
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TaskResource\Pages\ManageTasks;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Relaticle\CustomFields\Models\CustomField;

final class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationLabel = 'Tasks';

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Workspace';

    #[\Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                Select::make('companies')
                    ->label('Companies')
                    ->multiple()
                    ->relationship('companies', 'name'),
                Select::make('people')
                    ->label('People')
                    ->multiple()
                    ->relationship('people', 'name')
                    ->nullable(),
                Select::make('assignees')
                    ->label('Assignees')
                    ->multiple()
                    ->relationship('assignees', 'name')
                    ->nullable(),
                CustomFieldsComponent::make(),
            ])
            ->columns(1);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        $customField = CustomField::query()->where('name', 'status')->firstOrFail();
        $valueResolver = app(ValueResolvers::class);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->groups([
                Tables\Grouping\Group::make('status')
                    ->orderQueryUsing(function (Builder $query, string $direction) use ($customField) {
                        $table = $query->getModel()->getTable();
                        $key = $query->getModel()->getKeyName();

                        return $query->orderBy(
                            $customField->values()
                                ->select($customField->getValueColumn())
                                ->whereColumn('custom_field_values.entity_id', "$table.$key")
                                ->limit(1),
                            $direction
                        );
                    })
                    ->getTitleFromRecordUsing(fn (Task $record): ?string => $valueResolver->resolve($record, $customField)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        try {
                            DB::beginTransaction();

                            if ($record->getOriginal('assignee_id') !== $record->assignee_id) {
                                $recipient = $record->assignee;

                                Notification::make()
                                    ->title('You have been assigned task: #'.$record->id)
                                    ->sendToDatabase($recipient);
                            }

                            $record->update($data);

                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            throw $e;
                        }

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }
}

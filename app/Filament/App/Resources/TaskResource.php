<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TaskResource\Pages\ManageTasks;
use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
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

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationLabel = 'Tasks';
    protected static ?string $navigationIcon = 'heroicon-m-check-circle';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Workspace';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                CustomFieldsComponent::make()
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        $customField = CustomField::query()->where('name', 'status')->firstOrFail();
        $valueResolver = app(ValueResolvers::class);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
            ])
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
                    ->getTitleFromRecordUsing(fn(Task $record): ?string => $valueResolver->resolve($record, $customField)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        try {
                            DB::beginTransaction();

                            if ($record->getOriginal('assignee_id') != $record->assignee_id) {
                                $recipient = $record->assignee;

                                Notification::make()
                                    ->title('You have been assigned task: #' . $record->id)
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

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }
}

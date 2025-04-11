<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TaskResource\Pages\ManageTasks;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
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

    public static function table(Table $table): Table
    {
        $customFields = CustomField::query()->whereIn('code', ['status', 'priority'])->get()->keyBy('code');
        $valueResolver = app(ValueResolvers::class);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('assignees')
                    ->multiple()
                    ->relationship('assignees', 'name'),
            ])
            ->groups([
                Tables\Grouping\Group::make('status')
                    ->orderQueryUsing(function (Builder $query, string $direction) use ($customFields) {
                        $table = $query->getModel()->getTable();
                        $key = $query->getModel()->getKeyName();

                        return $query->orderBy(
                            $customFields->get('status')->values()
                                ->select($customFields->get('status')->getValueColumn())
                                ->whereColumn('custom_field_values.entity_id', "$table.$key")
                                ->limit(1),
                            $direction
                        );
                    })
                    ->getTitleFromRecordUsing(fn (Task $record): ?string => $valueResolver->resolve($record, $customFields->get('status'))),
                Tables\Grouping\Group::make('priority')
                    ->orderQueryUsing(function (Builder $query, string $direction) use ($customFields) {
                        $table = $query->getModel()->getTable();
                        $key = $query->getModel()->getKeyName();

                        return $query->orderBy(
                            $customFields->get('priority')->values()
                                ->select($customFields->get('priority')->getValueColumn())
                                ->whereColumn('custom_field_values.entity_id', "$table.$key")
                                ->limit(1),
                            $direction
                        );
                    })
                    ->getTitleFromRecordUsing(fn (Task $record): ?string => $valueResolver->resolve($record, $customFields->get('priority'))),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->using(function (Model $record, array $data): Model {
                            try {
                                DB::beginTransaction();

                                $record->update($data);

                                // TODO: Improve the logic to check if the task is already assigned to the user
                                // Send notifications to assignees if they haven't been notified about this task yet
                                if ($record->assignees->isNotEmpty()) {
                                    $record->assignees->each(function (Model $recipient) use ($record) {
                                        // Check if a notification for this task already exists for this user
                                        $notificationExists = $recipient->notifications()
                                            ->where('data->viewData->task_id', $record->id)
                                            ->exists();

                                        // Only send notification if one doesn't already exist
                                        if (! $notificationExists) {
                                            Notification::make()
                                                ->title('New Task Assignment: '.$record->title)
                                                ->actions([
                                                    Action::make('view')
                                                        ->button()
                                                        ->label('View Task')
                                                        ->url(ManageTasks::getUrl(['record' => $record]))
                                                        ->markAsRead(),
                                                ])
                                                ->icon('heroicon-o-check-circle')
                                                ->iconColor('primary')
                                                ->viewData(['task_id' => $record->id]) // Store task ID in notification data
                                                ->sendToDatabase($recipient);
                                        }
                                    });
                                }

                                DB::commit();
                            } catch (\Throwable $e) {
                                DB::rollBack();
                                throw $e;
                            }

                            return $record;
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
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

<?php

declare(strict_types=1);

namespace App\Livewire\App\ApiTokens;

use App\Livewire\BaseLivewireComponent;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\PersonalAccessToken;
use Filament\Support\Enums\Width;

final class ManageApiTokens extends BaseLivewireComponent implements HasTable
{
    use InteractsWithTable;

    /** @var array<string> */
    protected $listeners = ["tokenCreated" => '$refresh'];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn(): Builder => PersonalAccessToken::query()->where(
                    "tokenable_id",
                    $this->authUser()->getKey(),
                ),
            )
            ->columns([
                TextColumn::make("name")->label("Name")->searchable(),
                TextColumn::make("abilities")
                    ->label("Permissions")
                    ->badge()
                    ->formatStateUsing(
                        fn(string $state): string => $state === "*"
                            ? "All"
                            : ucfirst($state),
                    ),
                TextColumn::make("last_used_at")
                    ->label("Last Used")
                    ->since()
                    ->placeholder("Never"),
                TextColumn::make("created_at")->label("Created")->since(),
            ])
            ->actions([
                Action::make("permissions")
                    ->label("Permissions")
                    ->icon("heroicon-o-lock-closed")
                    ->iconButton()
                    ->tooltip("Edit Permissions")
                    ->modalHeading("API Token Permissions")
                    ->modalWidth(Width::Large)
                    ->fillForm(
                        fn(PersonalAccessToken $record): array => [
                            "permissions" => $record->abilities,
                        ],
                    )
                    ->schema([
                        CheckboxList::make("permissions")
                            ->label("Permissions")
                            ->options(
                                collect(Jetstream::$permissions)
                                    ->mapWithKeys(
                                        fn(string $permission): array => [
                                            $permission => ucfirst($permission),
                                        ],
                                    )
                                    ->all(),
                            )
                            ->columns(2),
                    ])
                    ->action(function (
                        PersonalAccessToken $record,
                        array $data,
                    ): void {
                        $record
                            ->forceFill([
                                "abilities" => Jetstream::validPermissions(
                                    $data["permissions"] ?? [],
                                ),
                            ])
                            ->save();

                        $this->sendNotification(
                            title: "API token permissions updated.",
                        );
                    }),
                DeleteAction::make()
                    ->iconButton()
                    ->record(
                        fn(
                            PersonalAccessToken $record,
                        ): PersonalAccessToken => $record,
                    )
                    ->after(
                        fn() => $this->sendNotification(
                            title: "API token deleted.",
                        ),
                    ),
            ])
            ->emptyStateHeading("No API tokens")
            ->emptyStateDescription("Create a token above to get started.")
            ->paginated(false);
    }

    public function render(): View
    {
        return view("livewire.app.api-tokens.manage-api-tokens");
    }
}

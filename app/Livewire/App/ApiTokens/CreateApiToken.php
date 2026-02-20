<?php

declare(strict_types=1);

namespace App\Livewire\App\ApiTokens;

use App\Livewire\BaseLivewireComponent;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\NewAccessToken;

final class CreateApiToken extends BaseLivewireComponent
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $plainTextToken = null;

    public function mount(): void
    {
        $this->form->fill([
            "permissions" => Jetstream::$defaultPermissions,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make("Create API Token")
                    ->aside()
                    ->description(
                        "API tokens allow third-party services to authenticate with our application on your behalf.",
                    )
                    ->schema([
                        TextInput::make("name")
                            ->label("Token Name")
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                Rule::unique("personal_access_tokens", "name")
                                    ->where(
                                        "tokenable_type",
                                        $this->authUser()->getMorphClass(),
                                    )
                                    ->where(
                                        "tokenable_id",
                                        $this->authUser()->getKey(),
                                    ),
                            ]),
                        CheckboxList::make("permissions")
                            ->label("Permissions")
                            ->required()
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
                        Actions::make([
                            Action::make("create")
                                ->label("Create")
                                ->submit("createToken"),
                        ]),
                    ]),
            ])
            ->statePath("data");
    }

    public function showTokenAction(): Action
    {
        return Action::make("showToken")
            ->modalHeading("API Token")
            ->modalDescription(
                'Please copy your new API token. For your security, it won\'t be shown again.',
            )
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel("Close")
            ->schema([
                TextInput::make("plainTextToken")
                    ->label("Token")
                    ->default($this->plainTextToken ?? "")
                    ->readOnly()
                    ->suffixAction(
                        Action::make("copyToken")
                            ->icon("heroicon-o-clipboard")
                            ->tooltip("Copy to clipboard")->alpineClickHandler("
                                window.navigator.clipboard.writeText(\$wire.plainTextToken);
                                \$tooltip('Copied!');
                            "),
                    ),
            ])
            ->after(fn() => ($this->plainTextToken = null));
    }

    public function createToken(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->sendRateLimitedNotification($exception);

            return;
        }

        $state = $this->form->getState();

        /** @var NewAccessToken $token */
        $token = $this->authUser()->createToken(
            $state["name"],
            Jetstream::validPermissions($state["permissions"] ?? []),
        );

        $this->plainTextToken = explode("|", $token->plainTextToken, 2)[1];

        $this->form->fill([
            "permissions" => Jetstream::$defaultPermissions,
        ]);

        $this->dispatch("tokenCreated");

        $this->mountAction("showToken");
    }

    public function render(): View
    {
        return view("livewire.app.api-tokens.create-api-token");
    }
}

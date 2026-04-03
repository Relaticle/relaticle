<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Jetstream\CreateTeam as CreateTeamAction;
use App\Enums\OnboardingReferralSource;
use App\Enums\OnboardingUseCase;
use App\Filament\Resources\CompanyResource;
use App\Models\Team;
use App\Models\User;
use App\Rules\ValidTeamSlug;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Override;

final class CreateTeam extends RegisterTenant
{
    private ?bool $wasFirstTeam = null;

    #[Override]
    public static function getLabel(): string
    {
        return 'Create Team';
    }

    #[Override]
    public function getHeading(): string
    {
        if ($this->isFirstTeam()) {
            return 'Welcome to Relaticle';
        }

        return 'Create your workspace';
    }

    #[Override]
    public function getSubheading(): string
    {
        if ($this->isFirstTeam()) {
            return "Let's set up your workspace in a few quick steps.";
        }

        return 'Choose a name for your team. This will also be used in your workspace URL.';
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        if (! $this->isFirstTeam()) {
            return $schema->components($this->getWorkspaceFormComponents());
        }

        return $schema
            ->components([
                Wizard::make([
                    $this->getUseCaseStep(),
                    $this->getAttributionStep(),
                    $this->getWorkspaceStep(),
                ])
                    ->submitAction(new HtmlString(
                        '<button type="submit" wire:click="register" class="fi-btn fi-btn-size-sm relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-primary gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50 w-full">Create workspace</button>'
                    )),
            ]);
    }

    private function getUseCaseStep(): Step
    {
        return Step::make('Use case')
            ->description('Tell us about your use case')
            ->icon('ri-apps-line')
            ->schema([
                ToggleButtons::make('onboarding_use_case')
                    ->label('What will you be using Relaticle for?')
                    ->options(
                        collect(OnboardingUseCase::cases())
                            ->mapWithKeys(fn (OnboardingUseCase $case): array => [
                                $case->value => $case->getLabel(),
                            ])
                            ->all()
                    )
                    ->icons(
                        collect(OnboardingUseCase::cases())
                            ->mapWithKeys(fn (OnboardingUseCase $case): array => [
                                $case->value => $case->getIcon(),
                            ])
                            ->all()
                    )
                    ->inline()
                    ->required()
                    ->live(),

                ToggleButtons::make('onboarding_context')
                    ->label('Please tell us more about your use case.')
                    ->options(function (Get $get): array {
                        $useCase = OnboardingUseCase::tryFrom($get('onboarding_use_case') ?? '');

                        if (! $useCase) {
                            return [];
                        }

                        return $useCase->getSubOptions();
                    })
                    ->inline()
                    ->multiple()
                    ->visible(function (Get $get): bool {
                        $useCase = OnboardingUseCase::tryFrom($get('onboarding_use_case') ?? '');

                        return $useCase !== null && $useCase->getSubOptions() !== [];
                    }),
            ]);
    }

    private function getAttributionStep(): Step
    {
        return Step::make('Attribution')
            ->description('Optional')
            ->icon('ri-question-line')
            ->schema([
                ToggleButtons::make('onboarding_referral_source')
                    ->label('How did you hear about us?')
                    ->options(
                        collect(OnboardingReferralSource::cases())
                            ->mapWithKeys(fn (OnboardingReferralSource $source): array => [
                                $source->value => $source->getLabel(),
                            ])
                            ->all()
                    )
                    ->inline(),
            ]);
    }

    private function getWorkspaceStep(): Step
    {
        return Step::make('Workspace')
            ->description('Name your workspace')
            ->icon('ri-building-line')
            ->schema($this->getWorkspaceFormComponents());
    }

    /**
     * @return array<Component>
     */
    private function getWorkspaceFormComponents(): array
    {
        $appHost = parse_url(url()->getAppUrl(), PHP_URL_HOST);

        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->placeholder('Acme Corp')
                ->autofocus()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                    if ($get('slug_auto_generated') === true || blank($get('slug'))) {
                        $set('slug', Str::slug($state ?? ''));
                        $set('slug_auto_generated', true);
                    }
                }),

            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->rules([new ValidTeamSlug])
                ->unique(Team::class, 'slug')
                ->prefix("{$appHost}/")
                ->helperText('Only lowercase letters, numbers, and hyphens are allowed.')
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set): void {
                    $set('slug_auto_generated', false);
                }),

            Hidden::make('slug_auto_generated')
                ->default(true)
                ->dehydrated(false),
        ];
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        if ($this->wasFirstTeam) {
            return OnboardingInvite::getUrl(tenant: $this->tenant);
        }

        return CompanyResource::getUrl('index', ['tenant' => $this->tenant]);
    }

    #[Override]
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = auth('web')->user();

        $this->wasFirstTeam = $this->isFirstTeam();

        return resolve(CreateTeamAction::class)->create($user, $data);
    }

    /**
     * @return array<Action|ActionGroup>
     */
    #[Override]
    protected function getFormActions(): array
    {
        if ($this->isFirstTeam()) {
            return [];
        }

        return parent::getFormActions();
    }

    #[Override]
    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->size(Size::Medium)
            ->label('Create workspace')
            ->submit('register');
    }

    private function isFirstTeam(): bool
    {
        /** @var User $user */
        $user = auth('web')->user();

        return ! $user->ownedTeams()->exists();
    }
}

<?php

declare(strict_types=1);

use App\Actions\Jetstream\CreateTeam as CreateTeamAction;
use App\Enums\OnboardingRole;
use App\Enums\OnboardingUseCase;
use App\Filament\Pages\CreateTeam;
use App\Filament\Resources\CompanyResource;
use App\Listeners\CreateTeamCustomFields;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Relaticle\OnboardSeed\OnboardSeedManager;

mutates(CreateTeam::class, CreateTeamAction::class, OnboardSeedManager::class, CreateTeamCustomFields::class);

it('renders the create team page with wizard for teamless users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->assertSuccessful()
        ->assertSee('Welcome to Relaticle');
});

it('renders simple form for users who already have a team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->assertSuccessful()
        ->assertSee('Create your workspace')
        ->assertDontSee('Welcome to Relaticle');
});

it('creates a team with onboarding fields', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Acme Corp',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = Team::query()->where('name', 'Acme Corp')->first();

    expect($team)->not->toBeNull()
        ->and($team->slug)->toBe('acme-corp')
        ->and($team->onboarding_role)->toBe(OnboardingRole::Founder)
        ->and($team->onboarding_use_case)->toBe(OnboardingUseCase::SalesPipeline);
});

it('creates a team with a custom slug', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Sales->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Acme Corp',
            'slug' => 'my-workspace',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = Team::query()->where('name', 'Acme Corp')->first();

    expect($team)->not->toBeNull()
        ->and($team->slug)->toBe('my-workspace');
});

it('validates slug format', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::General->value,
            'name' => 'Acme Corp',
            'slug' => 'INVALID SLUG!!',
        ])
        ->call('register')
        ->assertHasFormErrors(['slug']);
});

it('validates slug uniqueness', function (): void {
    $existingUser = User::factory()->create();
    Team::factory()->create(['slug' => 'taken-slug', 'user_id' => $existingUser->id]);

    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::General->value,
            'name' => 'Acme Corp',
            'slug' => 'taken-slug',
        ])
        ->call('register')
        ->assertHasFormErrors(['slug']);
});

it('requires a team name', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::General->value,
            'name' => '',
        ])
        ->call('register')
        ->assertHasFormErrors(['name']);
});

it('marks first team as personal team', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'My First Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->ownedTeams->first();

    expect($team->personal_team)->toBeTrue();
});

it('marks subsequent teams as non-personal', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'name' => 'Second Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $secondTeam = $user->fresh()->ownedTeams()->where('name', 'Second Team')->first();

    expect($secondTeam->personal_team)->toBeFalse();
});

it('redirects to companies index after team creation', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Redirect Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(CompanyResource::getUrl('index', ['tenant' => $user->fresh()->currentTeam]));
});

it('seeds sales demo data for sales pipeline use case', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Sales->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Sales Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    expect($team)->not->toBeNull();

    $companies = Company::where('team_id', $team->id)->pluck('name')->sort()->values();

    expect($companies)->toHaveCount(4)
        ->and($companies->all())->toBe(['Airbnb', 'Apple', 'Figma', 'Notion']);
});

it('seeds recruiting demo data for recruiting use case', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::Recruiting->value,
            'name' => 'Hiring Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    $companies = Company::where('team_id', $team->id)->pluck('name')->sort()->values();
    $people = People::where('team_id', $team->id)->pluck('name')->sort()->values();

    expect($companies)->toHaveCount(4)
        ->and($companies->all())->toBe(['Linear', 'Stripe', 'Supabase', 'Vercel'])
        ->and($people)->toHaveCount(4);
});

it('seeds marketing demo data for marketing use case', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Marketing->value,
            'onboarding_use_case' => OnboardingUseCase::Marketing->value,
            'name' => 'Marketing Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    $companies = Company::where('team_id', $team->id)->pluck('name')->sort()->values();

    expect($companies)->toHaveCount(4)
        ->and($companies->all())->toBe(['Canva', 'Clearbit', 'HubSpot', 'Mailchimp']);
});

it('seeds general demo data for general use case', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Other->value,
            'onboarding_use_case' => OnboardingUseCase::General->value,
            'name' => 'General Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    $companies = Company::where('team_id', $team->id)->pluck('name')->sort()->values();

    expect($companies)->toHaveCount(4)
        ->and($companies->all())->toBe(['Atlas Design Studio', 'Coastal Media', 'Horizon Labs', 'Summit Group']);
});

it('creates all custom fields for the first team', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Custom Fields Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    $fields = CustomField::withoutGlobalScopes()
        ->where('tenant_id', $team->id)
        ->get()
        ->groupBy('entity_type');

    expect($fields->get('company'))->toHaveCount(3)
        ->and($fields->get('people'))->toHaveCount(4)
        ->and($fields->get('opportunity'))->toHaveCount(3)
        ->and($fields->get('task'))->toHaveCount(4)
        ->and($fields->get('note'))->toHaveCount(1);
});

it('seeds people linked to their correct companies for sales', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Sales->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Link Test Team',
        ])
        ->call('register');

    $team = $user->fresh()->personalTeam();
    $companies = Company::where('team_id', $team->id)->pluck('id', 'name');
    $people = People::where('team_id', $team->id)->get();

    $expectedMapping = [
        'Tim Cook' => 'Apple',
        'Brian Chesky' => 'Airbnb',
        'Dylan Field' => 'Figma',
        'Ivan Zhao' => 'Notion',
    ];

    foreach ($people as $person) {
        $expectedCompany = $expectedMapping[$person->name];
        expect($person->company_id)->toBe($companies[$expectedCompany]);
    }
});

it('seeds tasks and opportunities with board positions', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Board Test Team',
        ])
        ->call('register');

    $team = $user->fresh()->personalTeam();

    $tasks = Task::where('team_id', $team->id)->get();
    $taskPositions = $tasks->pluck('order_column');
    expect($tasks)->toHaveCount(4)
        ->and($taskPositions->every(fn ($v) => $v !== null))->toBeTrue()
        ->and($taskPositions->unique())->toHaveCount(4);

    $opportunities = Opportunity::where('team_id', $team->id)->get();
    $opportunityPositions = $opportunities->pluck('order_column');
    expect($opportunities)->toHaveCount(4)
        ->and($opportunityPositions->every(fn ($v) => $v !== null))->toBeTrue()
        ->and($opportunityPositions->unique())->toHaveCount(4);
});

it('seeds custom field values correctly for sales', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Sales->value,
            'onboarding_use_case' => OnboardingUseCase::SalesPipeline->value,
            'name' => 'Values Test Team',
        ])
        ->call('register');

    $team = $user->fresh()->personalTeam();

    $apple = Company::where('team_id', $team->id)->where('name', 'Apple')->first();
    $companyFields = CustomField::withoutGlobalScopes()
        ->where('tenant_id', $team->id)
        ->forEntity(Company::class)
        ->pluck('id', 'code');

    $appleValues = CustomFieldValue::withoutGlobalScopes()
        ->where('entity_id', $apple->id)
        ->where('entity_type', $apple->getMorphClass())
        ->get()
        ->keyBy('custom_field_id');

    expect($appleValues[$companyFields['domains']]->json_value)->toContain('https://apple.com')
        ->and($appleValues[$companyFields['icp']]->boolean_value)->toBeTrue()
        ->and($appleValues[$companyFields['linkedin']]->json_value)->toContain('https://www.linkedin.com/company/apple');
});

it('does not require onboarding fields for subsequent teams', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'name' => 'Second Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->ownedTeams()->where('name', 'Second Team')->first();

    expect($team)->not->toBeNull()
        ->and($team->onboarding_role)->toBeNull()
        ->and($team->onboarding_use_case)->toBeNull();
});

it('shows branched use case options based on role', function (): void {
    $founderOptions = OnboardingRole::Founder->getUseCaseOptions();
    $salesOptions = OnboardingRole::Sales->getUseCaseOptions();

    expect($founderOptions)->toHaveCount(4)
        ->and($salesOptions)->toHaveCount(2)
        ->and(array_keys($founderOptions))->toContain(OnboardingUseCase::Recruiting->value)
        ->and(array_keys($salesOptions))->not->toContain(OnboardingUseCase::Recruiting->value);
});

it('maps use case to correct fixture set', function (): void {
    expect(OnboardingUseCase::SalesPipeline->getFixtureSet())->toBe('sales')
        ->and(OnboardingUseCase::Recruiting->getFixtureSet())->toBe('recruiting')
        ->and(OnboardingUseCase::Marketing->getFixtureSet())->toBe('marketing')
        ->and(OnboardingUseCase::General->getFixtureSet())->toBe('general');
});

it('seeds all entity types for each fixture set', function (OnboardingUseCase $useCase): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateTeam::class)
        ->fillForm([
            'onboarding_role' => OnboardingRole::Founder->value,
            'onboarding_use_case' => $useCase->value,
            'name' => "Team {$useCase->value}",
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $team = $user->fresh()->personalTeam();

    expect(Company::where('team_id', $team->id)->count())->toBe(4)
        ->and(People::where('team_id', $team->id)->count())->toBe(4)
        ->and(Opportunity::where('team_id', $team->id)->count())->toBe(4)
        ->and(Task::where('team_id', $team->id)->count())->toBe(4)
        ->and(Note::where('team_id', $team->id)->count())->toBe(5);
})->with([
    'sales' => [OnboardingUseCase::SalesPipeline],
    'recruiting' => [OnboardingUseCase::Recruiting],
    'marketing' => [OnboardingUseCase::Marketing],
    'general' => [OnboardingUseCase::General],
]);

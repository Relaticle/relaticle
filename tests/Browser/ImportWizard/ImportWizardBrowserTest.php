<?php

declare(strict_types=1);

use App\Models\User;
use Relaticle\ImportWizard\Livewire\ImportWizard;

mutates(ImportWizard::class);

it('can navigate the import wizard and upload a CSV file', function (): void {
    $user = User::factory()->withTeam()->create();
    $team = $user->ownedTeams()->first();
    $csvPath = base_path('tests/fixtures/imports/companies.csv');

    $this->visit('/app/login')
        ->type('[id="form.email"]', $user->email)
        ->type('[id="form.password"]', 'password')
        ->click('button.fi-btn')
        ->assertPathIs("/app/{$team->slug}/companies")
        // Navigate to the import page via the Filament page route
        ->navigate("/app/{$team->slug}/companies/import")
        ->assertSee('Import Companies')
        ->assertSee('Upload CSV')
        // Upload the CSV fixture file
        ->attach('uploadedFile', $csvPath)
        // After parsing, the continue button should appear
        ->assertSee('Continue')
        ->press('Continue')
        // Step 2: Map Columns
        ->assertSee('Map Columns');

    // The remaining wizard steps (review, preview, import) involve complex
    // server-side state transitions that are better covered by the existing
    // Livewire feature tests in tests/Feature/ImportWizard/.
});

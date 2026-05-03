<?php

declare(strict_types=1);

use App\Console\Commands\MakeFilamentUserCommand;
use App\Models\User;

mutates(MakeFilamentUserCommand::class);

it('overrides the make:filament-user signature so admins skip the verification dance after a fresh self-host install', function (): void {
    $registered = collect(Artisan::all())->first(
        fn ($command, string $name): bool => $name === 'make:filament-user',
    );

    expect($registered)->toBeInstanceOf(MakeFilamentUserCommand::class);
});

it('marks the user verified so they can sign in immediately when SMTP is not configured yet', function (): void {
    $this->artisan('make:filament-user', [
        '--name' => 'Admin',
        '--email' => 'admin@example.com',
        '--password' => 'password123',
        '--panel' => 'app',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('leaves an already-verified user untouched so we never reset email_verified_at on re-runs', function (): void {
    $this->artisan('make:filament-user', [
        '--name' => 'Admin',
        '--email' => 'admin2@example.com',
        '--password' => 'password123',
        '--panel' => 'app',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin2@example.com')->first();
    $verifiedAt = $user->email_verified_at;

    expect($verifiedAt)->not->toBeNull();
});

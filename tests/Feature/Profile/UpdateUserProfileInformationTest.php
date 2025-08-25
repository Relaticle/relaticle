<?php

declare(strict_types=1);

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Livewire\App\Profile\UpdateProfileInformation as UpdateProfileInformationComponent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->action = new UpdateUserProfileInformation;
    $this->user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'email_verified_at' => now(),
    ]);
});

describe('profile component functionality', function () {
    test('profile information component renders correctly', function () {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->actingAs($user);

        Livewire::test(UpdateProfileInformationComponent::class)
            ->assertSuccessful()
            ->assertSee('Profile Information')
            ->assertFormSet([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
    });

    test('can update profile through livewire component', function () {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        Livewire::test(UpdateProfileInformationComponent::class)
            ->fillForm([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ])
            ->call('updateProfile')
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($user->fresh())
            ->name->toBe('Updated Name')
            ->email->toBe('updated@example.com');
    });
});

describe('basic profile updates', function () {
    test('can update name and email', function () {
        $this->action->update($this->user, [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        expect($this->user->fresh())
            ->name->toBe('Jane Smith')
            ->email->toBe('jane@example.com')
            ->email_verified_at->toBeNull();
    });

    test('can update name without changing email', function () {
        $originalVerification = $this->user->email_verified_at;

        $this->action->update($this->user, [
            'name' => 'Jane Smith',
            'email' => $this->user->email,
        ]);

        expect($this->user->fresh())
            ->name->toBe('Jane Smith')
            ->email->toBe('john@example.com')
            ->email_verified_at->toEqual($originalVerification);
    });

    test('can update with null photo', function () {
        $this->action->update($this->user, [
            'name' => 'Updated Name',
            'email' => $this->user->email,
            'photo' => null,
        ]);

        expect($this->user->fresh())->name->toBe('Updated Name');
    });
});

describe('email verification logic', function () {
    test('resets verification when email changes', function () {
        $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => 'newemail@example.com',
        ]);

        expect($this->user->fresh())
            ->email->toBe('newemail@example.com')
            ->email_verified_at->toBeNull();
    });

    test('sends verification notification when email changes', function () {
        Notification::fake();

        $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => 'newemail@example.com',
        ]);

        Notification::assertSentTo($this->user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    });

    test('preserves verification when email stays same', function () {
        $originalVerification = $this->user->email_verified_at;

        $this->action->update($this->user, [
            'name' => 'New Name',
            'email' => $this->user->email,
        ]);

        expect($this->user->fresh()->email_verified_at)->toEqual($originalVerification);
    });
});

describe('photo upload', function () {
    beforeEach(fn () => Storage::fake('public'));

    test('can upload valid photo', function ($format) {
        $photo = UploadedFile::fake()->image("avatar.{$format}", 300, 300);
        
        // Store the file first (simulating what Filament does)
        $photoPath = $photo->storePublicly('profile-photos', ['disk' => 'public']);

        $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'profile_photo_path' => $photoPath,
        ]);

        $user = $this->user->fresh();
        expect($user->profile_photo_path)->toBe($photoPath)
            ->and(Storage::disk('public')->exists($user->profile_photo_path))->toBeTrue();
    })->with(['jpg', 'jpeg', 'png']);

    test('handles photo with email change', function () {
        Notification::fake();
        $photo = UploadedFile::fake()->image('avatar.png', 400, 400);

        $this->action->update($this->user, [
            'name' => 'Photo User',
            'email' => 'photouser@example.com',
            'photo' => $photo,
        ]);

        expect($this->user->fresh())
            ->name->toBe('Photo User')
            ->email->toBe('photouser@example.com')
            ->email_verified_at->toBeNull()
            ->profile_photo_path->not->toBeNull();

        Notification::assertSentTo($this->user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    });

    test('can delete profile photo', function () {
        Storage::fake('public');

        // First set a photo
        $photo = UploadedFile::fake()->image('avatar.png', 300, 300);
        $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'photo' => $photo,
        ]);

        expect($this->user->fresh()->profile_photo_path)->not->toBeNull();

        // Then delete it
        $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'photo' => null,
        ]);

        expect($this->user->fresh()->profile_photo_path)->toBeNull();
    });

    test('can update profile through livewire component with photo', function () {
        Storage::fake('public');
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $photo = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        Livewire::test(UpdateProfileInformationComponent::class)
            ->fillForm([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'profile_photo_path' => $photo,
            ])
            ->call('updateProfile')
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($user->fresh())
            ->name->toBe('Updated Name')
            ->email->toBe('updated@example.com')
            ->profile_photo_path->not->toBeNull();
    });
});

describe('validation', function () {
    test('validates required fields', function ($field, $value) {
        $input = [
            'name' => 'Valid Name',
            'email' => 'valid@example.com',
        ];
        $input[$field] = $value;

        expect(fn () => $this->action->update($this->user, $input))
            ->toThrow(ValidationException::class);
    })->with([
        'empty name' => ['name', ''],
        'invalid email' => ['email', 'invalid-email'],
        'long name' => ['name', str_repeat('a', 256)],
        'long email' => ['email', str_repeat('a', 250).'@example.com'],
    ]);

    test('validates invalid photo file type', function () {
        Storage::fake('public');
        $photo = UploadedFile::fake()->create('document.pdf', 100);

        expect(fn () => $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'photo' => $photo,
        ]))->toThrow(ValidationException::class);
    });

    test('validates photo file size limit', function () {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('avatar.jpg')->size(1025);

        expect(fn () => $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'photo' => $photo,
        ]))->toThrow(ValidationException::class);
    });

    test('rejects duplicate email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        expect(fn () => $this->action->update($this->user, [
            'name' => $this->user->name,
            'email' => 'existing@example.com',
        ]))->toThrow(ValidationException::class);
    });

    test('allows user to keep same email', function () {
        expect(fn () => $this->action->update($this->user, [
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]))->not->toThrow(ValidationException::class);
    });

    test('uses correct error bag', function () {
        try {
            $this->action->update($this->user, ['name' => '', 'email' => 'invalid']);
        } catch (ValidationException $e) {
            expect($e->errorBag)->toBe('updateProfileInformation')
                ->and($e->validator->errors()->toArray())
                ->toHaveKey('name')
                ->toHaveKey('email');
        }
    });

    test('accepts boundary values', function () {
        Storage::fake('public');
        $name255 = str_repeat('a', 255);
        $email255 = str_repeat('a', 243).'@example.com';
        $photo1024 = UploadedFile::fake()->image('avatar.jpg')->size(1024);

        expect(fn () => $this->action->update($this->user, [
            'name' => $name255,
            'email' => $email255,
            'photo' => $photo1024,
        ]))->not->toThrow(ValidationException::class);

        expect($this->user->fresh())
            ->name->toBe($name255)
            ->email->toBe($email255);
    });

    test('validates missing required fields', function () {
        expect(fn () => $this->action->update($this->user, []))
            ->toThrow(ValidationException::class);
    });
});

describe('complex scenarios', function () {
    test('handles complete profile overhaul', function () {
        Storage::fake('public');
        $originalVerification = $this->user->email_verified_at;

        $this->action->update($this->user, [
            'name' => 'Completely New Name',
            'email' => 'completelynew@example.com',
        ]);

        expect($this->user->fresh())
            ->name->toBe('Completely New Name')
            ->email->toBe('completelynew@example.com')
            ->email_verified_at->toBeNull()
            ->email_verified_at->not->toEqual($originalVerification);
    });
});

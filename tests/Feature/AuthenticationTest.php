<?php

declare(strict_types=1);

use App\Helpers\UrlHelper;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(UrlHelper::getAppUrl('login'));

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(UrlHelper::getAppUrl('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(UrlHelper::getAppUrl('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

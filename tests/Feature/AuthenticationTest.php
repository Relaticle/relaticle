<?php

declare(strict_types=1);

use App\Helpers\UrlHelper;
use App\Models\User;
use Filament\Facades\Filament;

test('login screen can be rendered', function () {
    $response = $this->get(UrlHelper::getAppUrl('login'));

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $response = $this->post(UrlHelper::getAppUrl('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    Filament::setTenant($user->currentTeam);

    $this->assertAuthenticated();
    // $response->assertRedirect(CompanyResource::getUrl('index'));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(UrlHelper::getAppUrl('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

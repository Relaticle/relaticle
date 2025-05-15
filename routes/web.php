<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\HomeController;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/auth/redirect/{provider}', RedirectController::class)
        ->name('auth.socialite.redirect');
    Route::get('/auth/callback/{provider}', CallbackController::class)
        ->name('auth.socialite.callback');

    Route::get('/login', function () {
        return redirect()->away(url()->getAppUrl('login'));
    })->name('login');

    Route::get('/register', function () {
        return redirect()->away(url()->getAppUrl('register'));
    })->name('register');

    Route::get('/forgot-password', function () {
        return redirect()->away(url()->getAppUrl('forgot-password'));
    })->name('password.request');
});

Route::get('/', HomeController::class);

Route::redirect('/dashboard', url()->getAppUrl())->name('dashboard');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

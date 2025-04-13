<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\DocumentationController;
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

    // Get app subdomain URL components from config
    $appHost = 'app.'.parse_url(config('app.url'))['host'];
    $scheme = parse_url(config('app.url'))['scheme'] ?? 'https';

    // Use proper Laravel helpers for external redirects
    Route::get('/login', function () use ($scheme, $appHost) {
        return redirect()->away($scheme.'://'.$appHost.'/login');
    })->name('login');

    Route::get('/register', function () use ($scheme, $appHost) {
        return redirect()->away($scheme.'://'.$appHost.'/register');
    })->name('register');
});

Route::get('/', HomeController::class);

Route::redirect('/dashboard', '/app')->name('dashboard');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

// Documentation routes
Route::prefix('documentation')->name('documentation.')->group(function () {
    Route::get('/', DocumentationController::class)->name('index');
    Route::get('/{type}', DocumentationController::class)->name('show');
});

<?php

declare(strict_types=1);

use App\Features\Documentation;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
use Relaticle\Documentation\Http\Controllers\DocumentationController;
use Spatie\MarkdownResponse\Middleware\ProvideMarkdownResponse;

Route::middleware([ProvideMarkdownResponse::class, EnsureFeaturesAreActive::using(Documentation::class)])->prefix('docs')->name('documentation.')->group(function (): void {
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('/search', [DocumentationController::class, 'search'])->name('search');
    Route::get('/{type}', [DocumentationController::class, 'show'])->name('show');
});

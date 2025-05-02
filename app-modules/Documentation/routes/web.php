<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Relaticle\Documentation\Http\Controllers\DocumentationController;

Route::prefix('documentation')->name('documentation.')->group(function () {
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('/{type}', [DocumentationController::class, 'show'])->name('show');
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Relaticle\Workflow\Http\Controllers\CanvasController;
use Relaticle\Workflow\Http\Controllers\WebhookTriggerController;
use Relaticle\Workflow\Http\Controllers\WorkflowApiController;

Route::prefix('workflow/api')
    ->middleware(config('workflow.middleware', ['web', 'auth']))
    ->group(function () {
        Route::post('workflows/{workflow}/trigger', [WorkflowApiController::class, 'trigger']);
        Route::post('webhooks/{workflow}', WebhookTriggerController::class);
        Route::get('workflows/{workflow}/canvas', [CanvasController::class, 'show']);
        Route::put('workflows/{workflow}/canvas', [CanvasController::class, 'update']);
    });

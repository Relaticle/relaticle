<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Relaticle\Workflow\Http\Controllers\CanvasController;
use Relaticle\Workflow\Http\Controllers\RunController;
use Relaticle\Workflow\Http\Controllers\WebhookTriggerController;
use Relaticle\Workflow\Http\Controllers\VariableController;
use Relaticle\Workflow\Http\Controllers\WorkflowApiController;
use Relaticle\Workflow\Http\Controllers\WorkflowLifecycleController;

Route::prefix('workflow/api')
    ->middleware(config('workflow.middleware', ['web', 'auth']))
    ->group(function () {
        Route::post('workflows/{workflow}/trigger', [WorkflowApiController::class, 'trigger']);
        Route::post('webhooks/{workflow}', WebhookTriggerController::class);
        Route::get('workflows/{workflow}/canvas', [CanvasController::class, 'show']);
        Route::put('workflows/{workflow}/canvas', [CanvasController::class, 'update']);
        Route::get('workflows/{workflow}/variables', [VariableController::class, 'index']);
        Route::get('workflows/{workflow}/runs', [RunController::class, 'index']);
        Route::get('workflow-runs/{run}', [RunController::class, 'show']);
        Route::post('workflows/{workflow}/publish', [WorkflowLifecycleController::class, 'publish']);
        Route::post('workflows/{workflow}/pause', [WorkflowLifecycleController::class, 'pause']);
        Route::post('workflows/{workflow}/archive', [WorkflowLifecycleController::class, 'archive']);
        Route::post('workflows/{workflow}/restore', [WorkflowLifecycleController::class, 'restore']);
    });

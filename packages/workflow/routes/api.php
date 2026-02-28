<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Relaticle\Workflow\Http\Controllers\WebhookTriggerController;
use Relaticle\Workflow\Http\Controllers\WorkflowApiController;

Route::prefix('workflow/api')->group(function () {
    Route::post('workflows/{workflow}/trigger', [WorkflowApiController::class, 'trigger']);
    Route::post('webhooks/{workflow}', WebhookTriggerController::class);
});

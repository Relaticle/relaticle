<?php

declare(strict_types=1);

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\PendingActionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->group(function (): void {
    Route::get('/chat/mentions', [ChatController::class, 'mentions'])->name('chat.mentions');
    Route::get('/chat/conversations', [ChatController::class, 'conversations'])->name('chat.conversations');
    Route::delete('/chat/conversations/{conversation}', [ChatController::class, 'destroyConversation'])->name('chat.conversations.destroy');

    Route::post('/chat/actions/{pendingAction}/approve', [PendingActionController::class, 'approve'])->name('chat.actions.approve');
    Route::post('/chat/actions/{pendingAction}/reject', [PendingActionController::class, 'reject'])->name('chat.actions.reject');

    Route::post('/chat/{conversation?}', [ChatController::class, 'send'])->name('chat.send');
});

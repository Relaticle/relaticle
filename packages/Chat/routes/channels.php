<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{userId}', fn (User $user, string $userId): bool => $user->getKey() === $userId);

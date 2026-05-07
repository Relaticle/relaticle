<?php

declare(strict_types=1);

namespace Relaticle\Chat\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class AiWriteCompleted
{
    use Dispatchable;

    public function __construct(
        public string $teamId,
        public string $entityType,
        public string $operation,
    ) {}
}

<?php

declare(strict_types=1);

namespace Relaticle\Chat\Data;

final readonly class CrmInsight
{
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public int $count,
        public string $severity,
        public string $prompt,
    ) {}
}

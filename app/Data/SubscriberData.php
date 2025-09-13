<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class SubscriberData extends Data
{
    public function __construct(
        public string $email,
        public ?string $first_name = '',
        public ?string $last_name = '',
        public ?array $tags = [],
        public bool $skip_confirmation = true,
    ) {}
}

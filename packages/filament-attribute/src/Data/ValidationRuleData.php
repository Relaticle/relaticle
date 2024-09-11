<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Data;

use Spatie\LaravelData\Data;

final class ValidationRuleData extends Data
{
    /**
     * Create a new instance of the ValidationRuleData class.
     *
     * @param  string  $name  The name of the validation rule.
     * @param  array<string, string>  $parameters  The parameters to be passed to the validation rule.
     * @return void
     */
    public function __construct(
        public string $name,
        public array $parameters = [],
    ) {}
}

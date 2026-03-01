<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Schema;

class FieldDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $isCustomField,
        public ?string $customFieldId = null,
        public array $options = [],
        public bool $required = false,
    ) {}
}

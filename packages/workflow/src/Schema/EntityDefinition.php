<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Schema;

class EntityDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $modelClass,
        public string $tableName,
    ) {}
}

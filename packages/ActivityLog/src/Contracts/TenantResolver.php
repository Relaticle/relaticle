<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Contracts;

interface TenantResolver
{
    public function resolve(): string|int|null;
}

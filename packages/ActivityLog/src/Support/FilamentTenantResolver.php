<?php

declare(strict_types=1);

namespace Relaticle\ActivityLog\Support;

use Filament\Facades\Filament;
use Relaticle\ActivityLog\Contracts\TenantResolver;

final class FilamentTenantResolver implements TenantResolver
{
    public function resolve(): string|int|null
    {
        return Filament::getTenant()?->getKey();
    }
}

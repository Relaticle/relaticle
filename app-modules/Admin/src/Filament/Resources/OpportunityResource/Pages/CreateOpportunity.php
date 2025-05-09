<?php

declare(strict_types=1);

namespace Relaticle\Admin\Filament\Resources\OpportunityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\Admin\Filament\Resources\OpportunityResource;

final class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;
}

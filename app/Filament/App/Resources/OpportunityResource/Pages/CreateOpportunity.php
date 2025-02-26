<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use App\Filament\App\Resources\OpportunityResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;
}

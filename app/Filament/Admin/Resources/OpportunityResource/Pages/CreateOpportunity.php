<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OpportunityResource\Pages;

use App\Filament\Admin\Resources\OpportunityResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;
}

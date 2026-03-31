<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Company;

use App\Ai\Tools\Chat\BaseReadShowTool;
use App\Http\Resources\V1\CompanyResource;
use App\Models\Company;

final class GetCompanyTool extends BaseReadShowTool
{
    public function description(): string
    {
        return 'Get a single company by ID with full details.';
    }

    protected function modelClass(): string
    {
        return Company::class;
    }

    protected function resourceClass(): string
    {
        return CompanyResource::class;
    }

    protected function entityLabel(): string
    {
        return 'Company';
    }
}

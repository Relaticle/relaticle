<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Company;

use App\Actions\Company\ListCompanies;
use App\Ai\Tools\Chat\BaseReadListTool;
use App\Http\Resources\V1\CompanyResource;

final class ListCompaniesTool extends BaseReadListTool
{
    public function description(): string
    {
        return 'List companies in the CRM with optional search and pagination.';
    }

    protected function actionClass(): string
    {
        return ListCompanies::class;
    }

    protected function resourceClass(): string
    {
        return CompanyResource::class;
    }

    protected function searchFilterName(): string
    {
        return 'name';
    }
}

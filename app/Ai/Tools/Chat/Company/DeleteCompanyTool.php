<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Company;

use App\Actions\Company\DeleteCompany;
use App\Ai\Tools\Chat\BaseWriteDeleteTool;
use App\Models\Company;

final class DeleteCompanyTool extends BaseWriteDeleteTool
{
    public function description(): string
    {
        return 'Propose deleting a company. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return Company::class;
    }

    protected function actionClass(): string
    {
        return DeleteCompany::class;
    }

    protected function entityLabel(): string
    {
        return 'Company';
    }

    protected function entityType(): string
    {
        return 'company';
    }
}

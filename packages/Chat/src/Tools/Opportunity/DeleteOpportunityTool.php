<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Opportunity;

use App\Actions\Opportunity\DeleteOpportunity;
use Relaticle\Chat\Tools\BaseWriteDeleteTool;
use App\Models\Opportunity;

final class DeleteOpportunityTool extends BaseWriteDeleteTool
{
    public function description(): string
    {
        return 'Propose deleting an opportunity/deal. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return Opportunity::class;
    }

    protected function actionClass(): string
    {
        return DeleteOpportunity::class;
    }

    protected function entityLabel(): string
    {
        return 'Opportunity';
    }

    protected function entityType(): string
    {
        return 'opportunity';
    }
}

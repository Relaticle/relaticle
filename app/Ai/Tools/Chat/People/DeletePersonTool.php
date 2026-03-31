<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\People;

use App\Actions\People\DeletePeople;
use App\Ai\Tools\Chat\BaseWriteDeleteTool;
use App\Models\People;

final class DeletePersonTool extends BaseWriteDeleteTool
{
    public function description(): string
    {
        return 'Propose deleting a person/contact. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return People::class;
    }

    protected function actionClass(): string
    {
        return DeletePeople::class;
    }

    protected function entityLabel(): string
    {
        return 'Person';
    }

    protected function entityType(): string
    {
        return 'people';
    }
}

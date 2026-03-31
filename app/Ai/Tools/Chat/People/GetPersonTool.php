<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\People;

use App\Ai\Tools\Chat\BaseReadShowTool;
use App\Http\Resources\V1\PeopleResource;
use App\Models\People;

final class GetPersonTool extends BaseReadShowTool
{
    public function description(): string
    {
        return 'Get a single person/contact by ID with full details.';
    }

    protected function modelClass(): string
    {
        return People::class;
    }

    protected function resourceClass(): string
    {
        return PeopleResource::class;
    }

    protected function entityLabel(): string
    {
        return 'Person';
    }
}

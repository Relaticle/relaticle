<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\CustomFieldResource;
use App\Models\CustomField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CustomFieldsController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $teamId = $user->currentTeam->getKey();

        $query = CustomField::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $teamId)
            ->active()
            ->with(['options' => fn ($q) => $q->withoutGlobalScopes()]);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }

        return CustomFieldResource::collection($query->get());
    }
}

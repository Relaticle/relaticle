<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Opportunity\CreateOpportunity;
use App\Actions\Opportunity\DeleteOpportunity;
use App\Actions\Opportunity\ListOpportunities;
use App\Actions\Opportunity\UpdateOpportunity;
use App\Enums\CreationSource;
use App\Http\Requests\Api\V1\StoreOpportunityRequest;
use App\Http\Requests\Api\V1\UpdateOpportunityRequest;
use App\Http\Resources\V1\OpportunityResource;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final readonly class OpportunitiesController
{
    public function index(Request $request, ListOpportunities $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return OpportunityResource::collection($action->execute($user));
    }

    public function store(StoreOpportunityRequest $request, CreateOpportunity $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $opportunity = $action->execute($user, $request->validated(), CreationSource::API);

        return new OpportunityResource($opportunity->load('customFieldValues.customField'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Opportunity $opportunity): OpportunityResource
    {
        Gate::authorize('view', $opportunity);

        $opportunity->loadMissing('customFieldValues.customField');

        $allowedIncludes = ['creator', 'company', 'contact'];
        $requested = array_intersect(
            explode(',', $request->query('include', '')),
            $allowedIncludes,
        );

        if ($requested !== []) {
            $opportunity->loadMissing($requested);
        }

        return new OpportunityResource($opportunity);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity, UpdateOpportunity $action): OpportunityResource
    {
        /** @var User $user */
        $user = $request->user();

        $opportunity = $action->execute($user, $opportunity, $request->validated());

        return new OpportunityResource($opportunity->load('customFieldValues.customField'));
    }

    public function destroy(Request $request, Opportunity $opportunity, DeleteOpportunity $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, $opportunity);

        return response()->json(null, 204);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\People\CreatePeople;
use App\Actions\People\DeletePeople;
use App\Actions\People\ListPeople;
use App\Actions\People\UpdatePeople;
use App\Enums\CreationSource;
use App\Http\Requests\Api\V1\StorePeopleRequest;
use App\Http\Requests\Api\V1\UpdatePeopleRequest;
use App\Http\Resources\V1\PeopleResource;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class PeopleController
{
    public function index(Request $request, ListPeople $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $people = $action->execute($user, $request->query());

        return PeopleResource::collection($people);
    }

    public function store(StorePeopleRequest $request, CreatePeople $action): PeopleResource
    {
        /** @var User $user */
        $user = $request->user();

        $person = $action->execute($user, $request->validated(), CreationSource::API);

        return new PeopleResource($person->loadMissing('customFieldValues.customField'));
    }

    public function show(People $person): PeopleResource
    {
        return new PeopleResource($person->loadMissing('customFieldValues.customField'));
    }

    public function update(UpdatePeopleRequest $request, People $person, UpdatePeople $action): PeopleResource
    {
        /** @var User $user */
        $user = $request->user();

        $person = $action->execute($user, $person, $request->validated());

        return new PeopleResource($person->loadMissing('customFieldValues.customField'));
    }

    public function destroy(Request $request, People $person, DeletePeople $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, $person);

        return response()->json(null, 204);
    }
}

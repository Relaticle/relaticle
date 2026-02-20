<?php

declare(strict_types=1);

namespace App\Mcp\Tools\People;

use App\Actions\People\UpdatePeople;
use App\Http\Resources\V1\PeopleResource;
use App\Models\People;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing person (contact) in the CRM.')]
#[IsIdempotent]
final class UpdatePeopleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The person ID to update.')->required(),
            'name' => $schema->string()->description('The person\'s full name.'),
            'company_id' => $schema->string()->description('The company this person belongs to.'),
        ];
    }

    public function handle(Request $request, UpdatePeople $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'company_id' => ['sometimes', 'string'],
        ]);

        /** @var People $person */
        $person = People::query()->findOrFail($validated['id']);
        unset($validated['id']);

        $person = $action->execute($user, $person, $validated);

        return Response::text(
            (new PeopleResource($person->loadMissing('customFieldValues.customField')))->toJson(JSON_PRETTY_PRINT)
        );
    }
}

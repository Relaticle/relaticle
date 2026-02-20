<?php

declare(strict_types=1);

namespace App\Mcp\Tools\People;

use App\Actions\People\CreatePeople;
use App\Enums\CreationSource;
use App\Http\Resources\V1\PeopleResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new person (contact) in the CRM.')]
final class CreatePeopleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The person\'s full name.')->required(),
            'company_id' => $schema->string()->description('The company this person belongs to.'),
        ];
    }

    public function handle(Request $request, CreatePeople $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['sometimes', 'string'],
        ]);

        $person = $action->execute($user, $validated, CreationSource::API);

        return Response::text(
            (new PeopleResource($person->loadMissing('customFieldValues.customField')))->toJson(JSON_PRETTY_PRINT)
        );
    }
}

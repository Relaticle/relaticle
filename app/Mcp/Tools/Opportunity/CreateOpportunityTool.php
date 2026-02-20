<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Opportunity;

use App\Actions\Opportunity\CreateOpportunity;
use App\Enums\CreationSource;
use App\Http\Resources\V1\OpportunityResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new opportunity (deal) in the CRM.')]
final class CreateOpportunityTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The opportunity name.')->required(),
            'company_id' => $schema->string()->description('The associated company ID.'),
            'contact_id' => $schema->string()->description('The associated contact (person) ID.'),
        ];
    }

    public function handle(Request $request, CreateOpportunity $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['sometimes', 'string'],
            'contact_id' => ['sometimes', 'string'],
        ]);

        $opportunity = $action->execute($user, $validated, CreationSource::API);

        return Response::text(
            new OpportunityResource($opportunity->loadMissing('customFieldValues.customField'))->toJson(JSON_PRETTY_PRINT)
        );
    }
}

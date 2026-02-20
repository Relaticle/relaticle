<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Opportunity;

use App\Actions\Opportunity\UpdateOpportunity;
use App\Http\Resources\V1\OpportunityResource;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing opportunity (deal) in the CRM.')]
#[IsIdempotent]
final class UpdateOpportunityTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The opportunity ID to update.')->required(),
            'name' => $schema->string()->description('The opportunity name.'),
            'company_id' => $schema->string()->description('The associated company ID.'),
            'contact_id' => $schema->string()->description('The associated contact (person) ID.'),
        ];
    }

    public function handle(Request $request, UpdateOpportunity $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'company_id' => ['sometimes', 'string'],
            'contact_id' => ['sometimes', 'string'],
        ]);

        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::query()->findOrFail($validated['id']);
        unset($validated['id']);

        $opportunity = $action->execute($user, $opportunity, $validated);

        return Response::text(
            (new OpportunityResource($opportunity->loadMissing('customFieldValues.customField')))->toJson(JSON_PRETTY_PRINT)
        );
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Opportunity;

use App\Actions\Opportunity\CreateOpportunity;
use Relaticle\Chat\Tools\BaseWriteCreateTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

final class CreateOpportunityTool extends BaseWriteCreateTool
{
    public function description(): string
    {
        return 'Propose creating a new opportunity/deal. Returns a proposal for user approval.';
    }

    protected function actionClass(): string
    {
        return CreateOpportunity::class;
    }

    protected function entityType(): string
    {
        return 'opportunity';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The opportunity name.')->required(),
            'company_id' => $schema->string()->description('The company ID.'),
            'contact_id' => $schema->string()->description('The contact/person ID.'),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        return array_filter([
            'name' => (string) $request->string('name'),
            'company_id' => $request['company_id'],
            'contact_id' => $request['contact_id'],
        ], fn (mixed $v): bool => $v !== null && $v !== '');
    }

    protected function buildDisplayData(Request $request): array
    {
        $name = (string) $request->string('name');

        return [
            'title' => 'Create Opportunity',
            'summary' => "Create opportunity \"{$name}\"",
            'fields' => [['label' => 'Name', 'value' => $name]],
        ];
    }
}

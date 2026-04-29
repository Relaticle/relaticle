<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Opportunity;

use App\Actions\Opportunity\UpdateOpportunity;
use App\Models\Opportunity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Tools\Request;
use Relaticle\Chat\Tools\BaseWriteUpdateTool;

final class UpdateOpportunityTool extends BaseWriteUpdateTool
{
    public function description(): string
    {
        return 'Propose updating an existing opportunity/deal. Returns a proposal for user approval.';
    }

    protected function modelClass(): string
    {
        return Opportunity::class;
    }

    protected function actionClass(): string
    {
        return UpdateOpportunity::class;
    }

    protected function entityType(): string
    {
        return 'opportunity';
    }

    protected function entityLabel(): string
    {
        return 'Opportunity';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The new opportunity name.'),
            'company_id' => $schema->string()->description('The new company ID.'),
            'contact_id' => $schema->string()->description('The new contact/person ID.'),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        return array_filter([
            'name' => $request['name'] ?? null,
            'company_id' => $request['company_id'] ?? null,
            'contact_id' => $request['contact_id'] ?? null,
        ], fn (mixed $v): bool => $v !== null);
    }

    protected function buildDisplayData(Request $request, Model $model): array
    {
        $fields = [];
        if (($request['name'] ?? null) !== null) {
            $fields[] = ['label' => 'Name', 'old' => $model->getAttribute('name'), 'new' => $request['name']];
        }

        return [
            'title' => 'Update Opportunity',
            'summary' => "Update opportunity \"{$model->getAttribute('name')}\"",
            'fields' => $fields,
        ];
    }
}

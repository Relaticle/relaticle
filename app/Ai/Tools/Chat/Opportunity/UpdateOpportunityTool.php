<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Opportunity;

use App\Actions\Opportunity\UpdateOpportunity;
use App\Ai\Tools\Chat\BaseWriteUpdateTool;
use App\Models\Opportunity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Tools\Request;

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
            'name' => $request['name'],
            'company_id' => $request['company_id'],
            'contact_id' => $request['contact_id'],
        ], fn (mixed $v): bool => $v !== null);
    }

    protected function buildDisplayData(Request $request, Model $model): array
    {
        $fields = [];
        if ($request['name'] !== null) {
            $fields[] = ['label' => 'Name', 'old' => $model->getAttribute('name'), 'new' => $request['name']];
        }

        return [
            'title' => 'Update Opportunity',
            'summary' => "Update opportunity \"{$model->getAttribute('name')}\"",
            'fields' => $fields,
        ];
    }
}

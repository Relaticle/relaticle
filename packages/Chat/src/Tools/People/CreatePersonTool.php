<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\People;

use App\Actions\People\CreatePeople;
use Relaticle\Chat\Tools\BaseWriteCreateTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

final class CreatePersonTool extends BaseWriteCreateTool
{
    public function description(): string
    {
        return 'Propose creating a new person/contact. Returns a proposal for user approval.';
    }

    protected function actionClass(): string
    {
        return CreatePeople::class;
    }

    protected function entityType(): string
    {
        return 'people';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The person name.')->required(),
            'company_id' => $schema->string()->description('The company ID to associate with.'),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        return array_filter([
            'name' => (string) $request->string('name'),
            'company_id' => $request['company_id'] ?? null,
        ], fn (mixed $v): bool => $v !== null && $v !== '');
    }

    protected function buildDisplayData(Request $request): array
    {
        $name = (string) $request->string('name');
        $fields = [['label' => 'Name', 'value' => $name]];
        if ($request['company_id'] ?? null) {
            $fields[] = ['label' => 'Company ID', 'value' => $request['company_id']];
        }

        return [
            'title' => 'Create Person',
            'summary' => "Create person \"{$name}\"",
            'fields' => $fields,
        ];
    }
}

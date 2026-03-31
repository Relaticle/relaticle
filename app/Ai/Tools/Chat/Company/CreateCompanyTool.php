<?php

declare(strict_types=1);

namespace App\Ai\Tools\Chat\Company;

use App\Actions\Company\CreateCompany;
use App\Ai\Tools\Chat\BaseWriteCreateTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

final class CreateCompanyTool extends BaseWriteCreateTool
{
    public function description(): string
    {
        return 'Propose creating a new company in the CRM. Returns a proposal for user approval.';
    }

    protected function actionClass(): string
    {
        return CreateCompany::class;
    }

    protected function entityType(): string
    {
        return 'company';
    }

    protected function entitySchema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The company name.')->required(),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        return ['name' => (string) $request->string('name')];
    }

    protected function buildDisplayData(Request $request): array
    {
        $name = (string) $request->string('name');

        return [
            'title' => 'Create Company',
            'summary' => "Create company \"{$name}\"",
            'fields' => [['label' => 'Name', 'value' => $name]],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\Chat\Tools\Company;

use App\Actions\Company\CreateCompany;
use App\Enums\CustomFields\CompanyField;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Relaticle\Chat\Tools\BaseWriteCreateTool;

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
            'domains' => $schema->array()
                ->items($schema->string())
                ->description('Optional list of website domains for the company (e.g. ["acme.com"]). Omit if unknown.'),
        ];
    }

    protected function extractActionData(Request $request): array
    {
        /** @var User $user */
        $user = auth()->user();

        $data = [
            'name' => (string) $request->string('name'),
            'account_owner_id' => $user->getKey(),
        ];

        $domains = $this->cleanDomains($request);
        if ($domains !== []) {
            $data['custom_fields'] = [
                CompanyField::DOMAINS->value => $domains,
            ];
        }

        return $data;
    }

    protected function buildDisplayData(Request $request): array
    {
        $name = (string) $request->string('name');
        $fields = [['label' => 'Name', 'value' => $name]];

        $domains = $this->cleanDomains($request);
        if ($domains !== []) {
            $fields[] = ['label' => 'Domains', 'value' => implode(', ', $domains)];
        }

        return [
            'title' => 'Create Company',
            'summary' => "Create company \"{$name}\"",
            'fields' => $fields,
        ];
    }

    /**
     * @return list<string>
     */
    private function cleanDomains(Request $request): array
    {
        $value = $request['domains'] ?? null;
        if (! is_array($value)) {
            return [];
        }

        $clean = [];
        foreach ($value as $domain) {
            if (is_string($domain) && $domain !== '') {
                $clean[] = $domain;
            }
        }

        return $clean;
    }
}

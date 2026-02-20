<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Actions\Company\CreateCompany;
use App\Enums\CreationSource;
use App\Http\Resources\V1\CompanyResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new company in the CRM.')]
final class CreateCompanyTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The company name.')->required(),
            'address' => $schema->string()->description('The company address.'),
            'phone' => $schema->string()->description('The company phone number.'),
            'country' => $schema->string()->description('The company country.'),
        ];
    }

    public function handle(Request $request, CreateCompany $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:100'],
        ]);

        $company = $action->execute($user, $validated, CreationSource::API);

        return Response::text(
            new CompanyResource($company->loadMissing('customFieldValues.customField'))->toJson(JSON_PRETTY_PRINT)
        );
    }
}

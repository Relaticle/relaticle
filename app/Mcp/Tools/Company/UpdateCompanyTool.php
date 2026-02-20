<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Company;

use App\Actions\Company\UpdateCompany;
use App\Http\Resources\V1\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing company in the CRM.')]
#[IsIdempotent]
final class UpdateCompanyTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The company ID to update.')->required(),
            'name' => $schema->string()->description('The company name.'),
            'address' => $schema->string()->description('The company address.'),
            'phone' => $schema->string()->description('The company phone number.'),
            'country' => $schema->string()->description('The company country.'),
        ];
    }

    public function handle(Request $request, UpdateCompany $action): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:100'],
        ]);

        /** @var Company $company */
        $company = Company::query()->findOrFail($validated['id']);
        unset($validated['id']);

        $company = $action->execute($user, $company, $validated);

        return Response::text(
            (new CompanyResource($company->loadMissing('customFieldValues.customField')))->toJson(JSON_PRETTY_PRINT)
        );
    }
}

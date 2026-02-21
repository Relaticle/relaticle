<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Company\CreateCompany;
use App\Actions\Company\DeleteCompany;
use App\Actions\Company\ListCompanies;
use App\Actions\Company\UpdateCompany;
use App\Enums\CreationSource;
use App\Http\Requests\Api\V1\StoreCompanyRequest;
use App\Http\Requests\Api\V1\UpdateCompanyRequest;
use App\Http\Resources\V1\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @group Companies
 *
 * Manage companies in your CRM workspace.
 */
final readonly class CompaniesController
{
    public function index(Request $request, ListCompanies $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return CompanyResource::collection($action->execute($user));
    }

    public function store(StoreCompanyRequest $request, CreateCompany $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $company = $action->execute($user, $request->validated(), CreationSource::API);

        return new CompanyResource($company->load('customFieldValues.customField'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Company $company): CompanyResource
    {
        Gate::authorize('view', $company);

        $company->loadMissing('customFieldValues.customField');

        return new CompanyResource($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): CompanyResource
    {
        /** @var User $user */
        $user = $request->user();

        $company = $action->execute($user, $company, $request->validated());

        return new CompanyResource($company->load('customFieldValues.customField'));
    }

    public function destroy(Request $request, Company $company, DeleteCompany $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, $company);

        return response()->json(null, 204);
    }
}

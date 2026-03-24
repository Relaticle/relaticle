<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;
use App\Support\CustomFieldMerger;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCompany
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Company $company, array $data): Company
    {
        abort_unless($user->can('update', $company), 403);

        $attributes = Arr::only($data, ['name', 'custom_fields']);

        $attributes = CustomFieldMerger::merge($company, $attributes);
        $attributes = HtmlSanitizer::sanitizeAttributes($attributes);

        return DB::transaction(function () use ($company, $attributes): Company {
            $company->update($attributes);

            return $company->refresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Opportunity;

use App\Models\Opportunity;
use App\Models\User;
use App\Support\CustomFieldMerger;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOpportunity
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Opportunity $opportunity, array $data): Opportunity
    {
        abort_unless($user->can('update', $opportunity), 403);

        $attributes = Arr::only($data, ['name', 'company_id', 'contact_id', 'custom_fields']);

        $attributes = CustomFieldMerger::merge($opportunity, $attributes);
        $attributes = HtmlSanitizer::sanitizeAttributes($attributes);

        return DB::transaction(function () use ($opportunity, $attributes): Opportunity {
            $opportunity->update($attributes);

            return $opportunity->refresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\PartnerSource;
use Illuminate\Validation\Rule;

/**
 * Shared validation rules for the four portfolio metadata fields.
 *
 * Referenced by StoreCompanyRequest, UpdateCompanyRequest, and MCP tool
 * entityRules() so that rules stay consistent across all entry points.
 */
final readonly class PortfolioMetadataRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function toRules(bool $isUpdate = false): array
    {
        $presence = $isUpdate ? 'sometimes' : 'nullable';

        return [
            'partner_source' => [$presence, 'nullable', Rule::enum(PartnerSource::class)],
            'geography' => [$presence, 'nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'concentration_percentage' => [$presence, 'nullable', 'numeric', 'min:0', 'max:100'],
            'is_recurring' => [$presence, 'boolean'],
        ];
    }
}

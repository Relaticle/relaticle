<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Concerns;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Models\CustomFieldValue;

trait FormatsCustomFields
{
    /**
     * @return array<string, mixed>
     */
    protected function formatCustomFields(Model $record): array
    {
        if (! $record->relationLoaded('customFieldValues')) {
            return [];
        }

        $result = [];

        /** @var CustomFieldValue $fieldValue */
        foreach ($record->getRelation('customFieldValues') as $fieldValue) {
            $result[$fieldValue->customField->code] = $fieldValue->getValue();
        }

        return $result;
    }
}

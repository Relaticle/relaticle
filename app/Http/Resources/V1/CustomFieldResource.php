<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Relaticle\CustomFields\Services\ValidationService;

/**
 * @property string $created_at
 * @property string $updated_at
 *
 * @mixin CustomField
 */
final class CustomFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $validationService = resolve(ValidationService::class);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'entity_type' => $this->entity_type,
            'required' => $validationService->isRequired($this->resource),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'label' => $option->name,
                'value' => $option->id,
            ])->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

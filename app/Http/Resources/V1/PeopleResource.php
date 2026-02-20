<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsCustomFields;
use App\Models\People;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin People
 */
final class PeopleResource extends JsonResource
{
    use FormatsCustomFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_id' => $this->company_id,
            'creation_source' => $this->creation_source,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'custom_fields' => $this->formatCustomFields($this->resource),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}

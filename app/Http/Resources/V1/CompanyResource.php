<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsCustomFields;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
final class CompanyResource extends JsonResource
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
            'address' => $this->address,
            'country' => $this->country,
            'phone' => $this->phone,
            'creation_source' => $this->creation_source,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'custom_fields' => $this->formatCustomFields($this->resource),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'people' => PeopleResource::collection($this->whenLoaded('people')),
            'opportunities' => OpportunityResource::collection($this->whenLoaded('opportunities')),
        ];
    }
}

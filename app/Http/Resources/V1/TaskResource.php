<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsCustomFields;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
final class TaskResource extends JsonResource
{
    use FormatsCustomFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'creation_source' => $this->creation_source,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'custom_fields' => $this->formatCustomFields($this->resource),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
            'people' => PeopleResource::collection($this->whenLoaded('people')),
            'opportunities' => OpportunityResource::collection($this->whenLoaded('opportunities')),
        ];
    }
}

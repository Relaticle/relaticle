<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\CustomFieldResource;
use App\Models\CustomField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * @group Custom Fields
 *
 * List custom field definitions configured for your workspace.
 */
final readonly class CustomFieldsController
{
    private const array ENTITY_TYPES = ['company', 'people', 'opportunity', 'task', 'note'];

    private const int MAX_PER_PAGE = 100;

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'entity_type' => ['sometimes', 'string', Rule::in(self::ENTITY_TYPES)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        /** @var User $user */
        $user = $request->user();

        $teamId = $user->currentTeam->getKey();

        $query = CustomField::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $teamId)
            ->active()
            ->with(['options' => fn (\Illuminate\Database\Eloquent\Relations\HasMany $q) => $q->withoutGlobalScopes()]);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }

        $perPage = (int) $request->query('per_page', '15');
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        return CustomFieldResource::collection($query->paginate($perPage));
    }
}

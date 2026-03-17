<?php

declare(strict_types=1);

namespace App\Mcp\Filters;

use App\Models\CustomField;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Spatie\QueryBuilder\Sorts\Sort;

final readonly class CustomFieldSort implements Sort
{
    public function __construct(
        private string $entityType,
    ) {}

    /**
     * @param  Builder<Model>  $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $field = $this->resolveField($property);

        if (! $field instanceof CustomField) {
            return;
        }

        $valueColumn = CustomFieldValue::getValueColumn($field->type);
        $model = $query->getModel();

        $query->orderBy(
            CustomFieldValue::query()
                ->select($valueColumn)
                ->whereColumn('entity_id', $model->getTable().'.id')
                ->where('entity_type', $model->getMorphClass())
                ->where('custom_field_id', $field->getKey())
                ->limit(1),
            $descending ? 'desc' : 'asc',
        );
    }

    private function resolveField(string $code): ?CustomField
    {
        return $this->resolveAllFields()->get($code);
    }

    /**
     * @return Collection<string, CustomField>
     */
    private function resolveAllFields(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Collection<string, CustomField> */
        return CustomField::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $user->currentTeam->getKey())
            ->where('entity_type', $this->entityType)
            ->active()
            ->get()
            ->keyBy('code');
    }
}

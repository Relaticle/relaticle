<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Services;

class TypedFieldResolver
{
    public function __construct(
        private readonly FieldResolverService $fieldResolver,
    ) {}

    /**
     * Get available fields filtered by type.
     *
     * @param  string  $workflowId
     * @param  string  $nodeId
     * @param  array<string>|null  $types  Types to include (null = all)
     * @return array<int, array{group: string, fields: array}>
     */
    public function getFieldsByType(string $workflowId, string $nodeId, ?array $types): array
    {
        $allGroups = $this->fieldResolver->getAvailableFields($workflowId, $nodeId);

        if ($types === null) {
            return $allGroups;
        }

        $filtered = [];
        foreach ($allGroups as $group) {
            $matchingFields = array_values(array_filter(
                $group['fields'],
                fn (array $field) => in_array($field['type'] ?? 'string', $types, true)
            ));

            if (!empty($matchingFields)) {
                $filtered[] = [
                    'group' => $group['group'],
                    'fields' => $matchingFields,
                ];
            }
        }

        return $filtered;
    }
}

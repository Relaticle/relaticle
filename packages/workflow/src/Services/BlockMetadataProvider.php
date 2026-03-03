<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Services;

use Relaticle\Workflow\Schema\RelaticleSchema;
use Relaticle\Workflow\WorkflowManager;

class BlockMetadataProvider
{
    public function __construct(
        private readonly WorkflowManager $manager,
        private readonly RelaticleSchema $schema,
    ) {}

    /**
     * Get the full block metadata manifest used by the frontend
     * for connection validation, field resolution, and operator filtering.
     *
     * @return array{
     *     blocks: array<string, array>,
     *     actions: array<string, array>,
     *     operators: array<string, array>,
     *     entities: list<string>,
     * }
     */
    public function getManifest(): array
    {
        return [
            'blocks' => $this->getBlockRules(),
            'actions' => $this->getActionMetadata(),
            'operators' => $this->getOperatorCompatibility(),
            'entities' => array_keys($this->schema->getEntities()),
        ];
    }

    /**
     * Connection rules for each block type.
     *
     * - maxOutgoing/maxIncoming: null means unlimited
     * - allowedTargets/allowedSources: which block types can connect
     * - isTerminal: true for end-of-flow blocks (stop)
     * - isRoot: true for start-of-flow blocks (trigger)
     * - edgeLabels: labels for outgoing edges (e.g. condition branches)
     *
     * @return array<string, array{
     *     maxOutgoing: int|null,
     *     maxIncoming: int|null,
     *     allowedTargets: list<string>,
     *     allowedSources: list<string>,
     *     isTerminal: bool,
     *     isRoot: bool,
     *     edgeLabels: list<string>,
     * }>
     */
    private function getBlockRules(): array
    {
        return [
            'trigger' => [
                'maxOutgoing' => 1,
                'maxIncoming' => 0,
                'allowedTargets' => ['action', 'condition', 'delay', 'loop'],
                'allowedSources' => [],
                'isTerminal' => false,
                'isRoot' => true,
                'edgeLabels' => [],
            ],
            'action' => [
                'maxOutgoing' => 1,
                'maxIncoming' => null,
                'allowedTargets' => ['action', 'condition', 'delay', 'loop', 'stop'],
                'allowedSources' => ['trigger', 'action', 'condition', 'delay', 'loop'],
                'isTerminal' => false,
                'isRoot' => false,
                'edgeLabels' => [],
            ],
            'condition' => [
                'maxOutgoing' => 2,
                'maxIncoming' => null,
                'allowedTargets' => ['action', 'condition', 'delay', 'loop', 'stop'],
                'allowedSources' => ['trigger', 'action', 'delay', 'loop'],
                'isTerminal' => false,
                'isRoot' => false,
                'edgeLabels' => ['does match', 'does not match'],
            ],
            'delay' => [
                'maxOutgoing' => 1,
                'maxIncoming' => null,
                'allowedTargets' => ['action', 'condition', 'loop', 'stop'],
                'allowedSources' => ['trigger', 'action', 'condition', 'delay', 'loop'],
                'isTerminal' => false,
                'isRoot' => false,
                'edgeLabels' => [],
            ],
            'loop' => [
                'maxOutgoing' => 1,
                'maxIncoming' => null,
                'allowedTargets' => ['action', 'condition', 'delay', 'stop'],
                'allowedSources' => ['trigger', 'action', 'condition', 'delay', 'loop'],
                'isTerminal' => false,
                'isRoot' => false,
                'edgeLabels' => [],
            ],
            'stop' => [
                'maxOutgoing' => 0,
                'maxIncoming' => null,
                'allowedTargets' => [],
                'allowedSources' => ['action', 'condition', 'delay', 'loop'],
                'isTerminal' => true,
                'isRoot' => false,
                'edgeLabels' => [],
            ],
        ];
    }

    /**
     * Build action metadata from all registered actions.
     *
     * Each entry includes:
     * - category: UI grouping label
     * - requiredConfig: list of required config field keys
     * - inheritsEntityFromTrigger: whether this action uses the trigger's entity type
     *
     * @return array<string, array{
     *     category: string,
     *     requiredConfig: list<string>,
     *     inheritsEntityFromTrigger: bool,
     * }>
     */
    private function getActionMetadata(): array
    {
        $metadata = [];
        $entityActions = ['create_record', 'update_record', 'find_record', 'delete_record'];

        foreach ($this->manager->getActions() as $key => $actionClass) {
            $requiredConfig = [];
            foreach ($actionClass::configSchema() as $field => $schema) {
                if (! empty($schema['required'])) {
                    $requiredConfig[] = $field;
                }
            }

            $metadata[$key] = [
                'category' => $actionClass::category(),
                'requiredConfig' => $requiredConfig,
                'inheritsEntityFromTrigger' => in_array($key, $entityActions, true),
            ];
        }

        return $metadata;
    }

    /**
     * Operator-to-type compatibility map used by the condition builder
     * to show only applicable operators for a given field type.
     *
     * @return array<string, array{applicableTo: list<string>}>
     */
    private function getOperatorCompatibility(): array
    {
        return [
            'equals' => ['applicableTo' => ['string', 'number', 'boolean', 'date']],
            'not_equals' => ['applicableTo' => ['string', 'number', 'boolean', 'date']],
            'contains' => ['applicableTo' => ['string']],
            'greater_than' => ['applicableTo' => ['number', 'date']],
            'less_than' => ['applicableTo' => ['number', 'date']],
            'is_empty' => ['applicableTo' => ['string', 'number', 'boolean', 'date']],
            'is_not_empty' => ['applicableTo' => ['string', 'number', 'boolean', 'date']],
            'in' => ['applicableTo' => ['string', 'number']],
        ];
    }
}

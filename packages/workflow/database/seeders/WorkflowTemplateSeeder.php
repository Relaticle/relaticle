<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Relaticle\Workflow\Models\WorkflowTemplate;

class WorkflowTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $i => $template) {
            WorkflowTemplate::updateOrCreate(
                ['name' => $template['name']],
                array_merge($template, ['sort_order' => $i]),
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function getTemplates(): array
    {
        return [
            [
                'name' => 'Welcome email on new contact',
                'description' => 'Automatically send a welcome email when a new contact is created.',
                'category' => 'Communication',
                'icon' => 'mail',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'created'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Welcome!', 'body' => 'Thanks for joining us.'], 'position_x' => 400, 'position_y' => 260],
                    ],
                    'edges' => [['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1']],
                ],
            ],
            [
                'name' => 'Assign lead to sales rep',
                'description' => 'When a new lead is created, automatically assign it to a sales representative.',
                'category' => 'Sales',
                'icon' => 'user-plus',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'created'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'update_record', 'config' => ['field' => 'owner_id'], 'position_x' => 400, 'position_y' => 260],
                    ],
                    'edges' => [['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1']],
                ],
            ],
            [
                'name' => 'Follow-up reminder after 3 days',
                'description' => 'Send a follow-up reminder 3 days after a contact is created.',
                'category' => 'Communication',
                'icon' => 'clock',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'created'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'delay-1', 'type' => 'delay', 'config' => ['duration' => 3, 'unit' => 'days'], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Following up'], 'position_x' => 400, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'delay-1'],
                        ['edge_id' => 'e2', 'source' => 'delay-1', 'target' => 'action-1'],
                    ],
                ],
            ],
            [
                'name' => 'Notify team on high-value deal',
                'description' => 'When a deal value exceeds a threshold, notify the team via broadcast.',
                'category' => 'Sales',
                'icon' => 'bell',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'updated'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'condition-1', 'type' => 'condition', 'config' => ['conditions' => [['field' => 'trigger.record.value', 'operator' => 'greater_than', 'value' => '10000']]], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'broadcast_message', 'config' => ['message' => 'High-value deal detected!'], 'position_x' => 250, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'condition-1'],
                        ['edge_id' => 'e2', 'source' => 'condition-1', 'target' => 'action-1', 'condition_label' => 'Yes'],
                    ],
                ],
            ],
            [
                'name' => 'Auto-tag contacts by company size',
                'description' => 'Automatically classify and tag contacts based on their company size.',
                'category' => 'Organization',
                'icon' => 'tag',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'created'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'classify', 'config' => ['field' => 'company_size'], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'update_record', 'config' => ['field' => 'tags'], 'position_x' => 400, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
                        ['edge_id' => 'e2', 'source' => 'action-1', 'target' => 'action-2'],
                    ],
                ],
            ],
            [
                'name' => 'Dead deal cleanup after 30 days',
                'description' => 'Archive deals that have been stale for more than 30 days.',
                'category' => 'Sales',
                'icon' => 'archive',
                'definition' => [
                    'trigger_type' => 'time_based',
                    'trigger_config' => ['cron' => '0 8 * * 1'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'find_record', 'config' => ['filter' => 'stale_30_days'], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'loop-1', 'type' => 'loop', 'config' => ['collection' => 'steps.action-1.output.records'], 'position_x' => 400, 'position_y' => 440],
                        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'update_record', 'config' => ['field' => 'status', 'value' => 'archived'], 'position_x' => 400, 'position_y' => 620],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
                        ['edge_id' => 'e2', 'source' => 'action-1', 'target' => 'loop-1'],
                        ['edge_id' => 'e3', 'source' => 'loop-1', 'target' => 'action-2'],
                    ],
                ],
            ],
            [
                'name' => 'Birthday/anniversary reminder',
                'description' => 'Send a reminder email for contact birthdays or work anniversaries.',
                'category' => 'Communication',
                'icon' => 'cake',
                'definition' => [
                    'trigger_type' => 'time_based',
                    'trigger_config' => ['cron' => '0 9 * * *'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'find_record', 'config' => ['filter' => 'birthday_today'], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'loop-1', 'type' => 'loop', 'config' => ['collection' => 'steps.action-1.output.records'], 'position_x' => 400, 'position_y' => 440],
                        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Happy Birthday!'], 'position_x' => 400, 'position_y' => 620],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
                        ['edge_id' => 'e2', 'source' => 'action-1', 'target' => 'loop-1'],
                        ['edge_id' => 'e3', 'source' => 'loop-1', 'target' => 'action-2'],
                    ],
                ],
            ],
            [
                'name' => 'Escalate stale deals',
                'description' => 'If a deal has not been updated in 7 days, notify the manager.',
                'category' => 'Sales',
                'icon' => 'alert-triangle',
                'definition' => [
                    'trigger_type' => 'time_based',
                    'trigger_config' => ['cron' => '0 9 * * 1'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'find_record', 'config' => ['filter' => 'stale_7_days'], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'condition-1', 'type' => 'condition', 'config' => ['conditions' => [['field' => 'steps.action-1.output.count', 'operator' => 'greater_than', 'value' => '0']]], 'position_x' => 400, 'position_y' => 440],
                        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Stale deals require attention'], 'position_x' => 250, 'position_y' => 620],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
                        ['edge_id' => 'e2', 'source' => 'action-1', 'target' => 'condition-1'],
                        ['edge_id' => 'e3', 'source' => 'condition-1', 'target' => 'action-2', 'condition_label' => 'Yes'],
                    ],
                ],
            ],
            [
                'name' => 'Send webhook on deal won',
                'description' => 'Notify an external system via webhook when a deal is marked as won.',
                'category' => 'Integration',
                'icon' => 'zap',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'updated'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'condition-1', 'type' => 'condition', 'config' => ['conditions' => [['field' => 'trigger.record.stage', 'operator' => 'equals', 'value' => 'won']]], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_webhook', 'config' => ['url' => 'https://example.com/webhook'], 'position_x' => 250, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'condition-1'],
                        ['edge_id' => 'e2', 'source' => 'condition-1', 'target' => 'action-1', 'condition_label' => 'Yes'],
                    ],
                ],
            ],
            [
                'name' => 'AI summarize new records',
                'description' => 'Automatically generate an AI summary when a new record is created.',
                'category' => 'AI',
                'icon' => 'sparkles',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'created'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'summarize', 'config' => [], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'update_record', 'config' => ['field' => 'summary'], 'position_x' => 400, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'action-1'],
                        ['edge_id' => 'e2', 'source' => 'action-1', 'target' => 'action-2'],
                    ],
                ],
            ],
            [
                'name' => 'Conditional email by deal stage',
                'description' => 'Send different emails based on which stage a deal moves to.',
                'category' => 'Communication',
                'icon' => 'git-branch',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'updated'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'condition-1', 'type' => 'condition', 'config' => ['conditions' => [['field' => 'trigger.record.stage', 'operator' => 'equals', 'value' => 'negotiation']]], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Deal in negotiation'], 'position_x' => 250, 'position_y' => 440],
                        ['node_id' => 'action-2', 'type' => 'action', 'action_type' => 'send_email', 'config' => ['subject' => 'Deal stage changed'], 'position_x' => 550, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'condition-1'],
                        ['edge_id' => 'e2', 'source' => 'condition-1', 'target' => 'action-1', 'condition_label' => 'Yes'],
                        ['edge_id' => 'e3', 'source' => 'condition-1', 'target' => 'action-2', 'condition_label' => 'No'],
                    ],
                ],
            ],
            [
                'name' => 'Celebrate deal won',
                'description' => 'Trigger a celebration notification when a deal is closed as won.',
                'category' => 'Fun',
                'icon' => 'party-popper',
                'definition' => [
                    'trigger_type' => 'record_event',
                    'trigger_config' => ['event' => 'updated'],
                    'nodes' => [
                        ['node_id' => 'trigger-1', 'type' => 'trigger', 'position_x' => 400, 'position_y' => 80],
                        ['node_id' => 'condition-1', 'type' => 'condition', 'config' => ['conditions' => [['field' => 'trigger.record.stage', 'operator' => 'equals', 'value' => 'won']]], 'position_x' => 400, 'position_y' => 260],
                        ['node_id' => 'action-1', 'type' => 'action', 'action_type' => 'celebration', 'config' => ['message' => 'Deal won!'], 'position_x' => 250, 'position_y' => 440],
                    ],
                    'edges' => [
                        ['edge_id' => 'e1', 'source' => 'trigger-1', 'target' => 'condition-1'],
                        ['edge_id' => 'e2', 'source' => 'condition-1', 'target' => 'action-1', 'condition_label' => 'Yes'],
                    ],
                ],
            ],
        ];
    }
}

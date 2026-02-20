<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('CRM data model schema describing available entities and their fields.')]
#[Uri('relaticle://resources/crm-schema')]
#[MimeType('application/json')]
final class CrmSchemaResource extends Resource
{
    public function handle(Request $request): Response
    {
        $schema = [
            'entities' => [
                'company' => [
                    'description' => 'Organizations and businesses tracked in the CRM.',
                    'fields' => [
                        'id' => 'string (ULID)',
                        'name' => 'string (required)',
                        'address' => 'string',
                        'phone' => 'string',
                        'country' => 'string',
                        'creation_source' => 'enum: web, system, import, api',
                        'custom_fields' => 'object (dynamic key-value pairs)',
                    ],
                    'relationships' => ['people', 'opportunities', 'tasks', 'notes'],
                ],
                'people' => [
                    'description' => 'Individual contacts associated with companies.',
                    'fields' => [
                        'id' => 'string (ULID)',
                        'name' => 'string (required)',
                        'company_id' => 'string (optional, links to company)',
                        'creation_source' => 'enum: web, system, import, api',
                        'custom_fields' => 'object (dynamic key-value pairs)',
                    ],
                    'relationships' => ['company', 'opportunities', 'tasks', 'notes'],
                ],
                'opportunity' => [
                    'description' => 'Sales deals or business opportunities.',
                    'fields' => [
                        'id' => 'string (ULID)',
                        'name' => 'string (required)',
                        'company_id' => 'string (optional)',
                        'contact_id' => 'string (optional, links to people)',
                        'creation_source' => 'enum: web, system, import, api',
                        'custom_fields' => 'object (dynamic key-value pairs)',
                    ],
                    'relationships' => ['company', 'contact', 'tasks', 'notes'],
                ],
                'task' => [
                    'description' => 'Action items and to-dos.',
                    'fields' => [
                        'id' => 'string (ULID)',
                        'title' => 'string (required)',
                        'creation_source' => 'enum: web, system, import, api',
                        'custom_fields' => 'object (dynamic key-value pairs)',
                    ],
                    'relationships' => ['assignees', 'companies', 'people', 'opportunities'],
                ],
                'note' => [
                    'description' => 'Free-form notes attached to CRM records.',
                    'fields' => [
                        'id' => 'string (ULID)',
                        'title' => 'string (required)',
                        'creation_source' => 'enum: web, system, import, api',
                        'custom_fields' => 'object (dynamic key-value pairs)',
                    ],
                    'relationships' => ['companies', 'people', 'opportunities'],
                ],
            ],
        ];

        return Response::text(json_encode($schema, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}

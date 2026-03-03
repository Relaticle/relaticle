<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Engine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

class VariableResolver
{
    /**
     * Attribute names that must never be exposed via workflow variables.
     */
    private const SENSITIVE_ATTRIBUTES = [
        'password', 'remember_token', 'api_token', 'secret',
        'webhook_secret', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    /**
     * Built-in variable resolvers keyed by variable name.
     *
     * @var array<string, callable(): string>
     */
    private array $builtInVariables;

    public function __construct()
    {
        $this->builtInVariables = [
            'now' => static fn (): string => Carbon::now()->toIso8601String(),
            'today' => static fn (): string => Carbon::now()->toDateString(),
        ];
    }

    /**
     * Resolve all {{variable}} placeholders in a template string.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolve(string $template, array $context): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/',
            fn (array $matches): string => $this->resolveVariable(trim($matches[1]), $context),
            $template,
        );
    }

    /**
     * Recursively resolve all string values in an array.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function resolveArray(array $config, array $context): array
    {
        $resolved = [];

        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $resolved[$key] = $this->resolve($value, $context);
            } elseif (is_array($value)) {
                $resolved[$key] = $this->resolveArray($value, $context);
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Resolve a single variable path to its value.
     *
     * @param  array<string, mixed>  $context
     */
    private function resolveVariable(string $path, array $context): string
    {
        // Check built-in variables first
        if (isset($this->builtInVariables[$path])) {
            return ($this->builtInVariables[$path])();
        }

        $value = $this->resolveFromContext($path, $context);

        if ($value === null) {
            Log::warning("Workflow variable '{$path}' could not be resolved.");

            return '';
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Resolve a dotted path from the context, with Eloquent model awareness.
     */
    private function resolveFromContext(string $path, array $context): mixed
    {
        $segments = explode('.', $path);
        $current = $context;

        foreach ($segments as $i => $segment) {
            if ($current instanceof Model) {
                $remainingSegments = array_slice($segments, $i);

                return $this->resolveFromModel($current, $remainingSegments);
            }

            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Resolve a path from an Eloquent model, supporting attributes, custom fields, and relationships.
     *
     * @param  string[]  $segments
     */
    private function resolveFromModel(Model $model, array $segments): mixed
    {
        if (empty($segments)) {
            return null;
        }

        $first = $segments[0];
        $rest = array_slice($segments, 1);

        // Custom field access: custom.{code}
        if ($first === 'custom' && !empty($rest)) {
            return $this->resolveCustomField($model, $rest[0]);
        }

        // Block access to sensitive attributes
        if ($this->isSensitiveAttribute($model, $first)) {
            return '';
        }

        // Try as an attribute (no schema introspection — use getAttribute directly)
        try {
            $value = $model->getAttribute($first);

            if ($value !== null || array_key_exists($first, $model->getAttributes())) {
                if (empty($rest)) {
                    return $value;
                }

                if ($value instanceof Model) {
                    return $this->resolveFromModel($value, $rest);
                }

                return data_get($value, implode('.', $rest));
            }
        } catch (\Throwable) {
            // Attribute access failed, try as relationship
        }

        // Try as a relationship
        if (method_exists($model, $first)) {
            try {
                $related = $model->{$first};

                if ($related instanceof Model) {
                    return empty($rest) ? null : $this->resolveFromModel($related, $rest);
                }

                if (empty($rest)) {
                    return $related;
                }

                return data_get($related, implode('.', $rest));
            } catch (\Throwable) {
                // Method exists but is not a relationship or failed
            }
        }

        return null;
    }

    /**
     * Check if an attribute is sensitive and should not be exposed.
     */
    private function isSensitiveAttribute(Model $model, string $attribute): bool
    {
        if (in_array($attribute, $model->getHidden(), true)) {
            return true;
        }

        return in_array(strtolower($attribute), self::SENSITIVE_ATTRIBUTES, true);
    }

    /**
     * Resolve a custom field value by its code.
     */
    private function resolveCustomField(Model $model, string $fieldCode): mixed
    {
        if (!$model instanceof HasCustomFields) {
            return null;
        }

        try {
            // Eager load custom field values and definitions to prevent N+1 queries
            if (!$model->relationLoaded('customFieldValues')) {
                $model->load('customFieldValues.customField');
            }

            $customField = $model->customFields()->where('code', $fieldCode)->first();

            if (!$customField) {
                return null;
            }

            return $model->getCustomFieldValue($customField);
        } catch (\Throwable) {
            return null;
        }
    }
}

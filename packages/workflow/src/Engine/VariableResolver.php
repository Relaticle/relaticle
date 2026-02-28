<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Engine;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class VariableResolver
{
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
     * Variables are resolved in the following order:
     * 1. Built-in variables (now, today)
     * 2. Context values using dot-notation (e.g., record.name, trigger.user.name)
     * 3. Missing variables resolve to an empty string
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
     * Non-string values are left untouched. Nested arrays are resolved recursively.
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

        // Resolve from context using dot-notation
        $value = data_get($context, $path);

        if ($value === null) {
            Log::warning("Workflow variable '{$path}' could not be resolved.");

            return '';
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }

        return '';
    }
}

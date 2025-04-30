<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class FixtureRegistry
{
    /**
     * Registry of entities
     *
     * @var array<string, array<string, Model>>
     */
    private static array $registry = [];

    /**
     * Register an entity in the registry
     *
     * @param string $type The entity type (e.g., 'companies', 'people')
     * @param string $key The entity key
     * @param Model $entity The entity model
     */
    public static function register(string $type, string $key, Model $entity): void
    {
        if (!isset(self::$registry[$type])) {
            self::$registry[$type] = [];
        }

        self::$registry[$type][$key] = $entity;
    }

    /**
     * Get an entity from the registry
     *
     * @param string $type The entity type
     * @param string $key The entity key
     * @return Model|null The entity or null if not found
     */
    public static function get(string $type, string $key): ?Model
    {
        return self::$registry[$type][$key] ?? null;
    }

    /**
     * Get all entities of a specific type
     *
     * @param string $type The entity type
     * @return Collection<string, Model> Collection of entities
     */
    public static function all(string $type): Collection
    {
        return collect(self::$registry[$type] ?? []);
    }

    /**
     * Check if an entity exists in the registry
     *
     * @param string $type The entity type
     * @param string $key The entity key
     * @return bool Whether the entity exists
     */
    public static function has(string $type, string $key): bool
    {
        return isset(self::$registry[$type][$key]);
    }

    /**
     * Clear the registry for a specific type or all types
     *
     * @param string|null $type The entity type to clear or null for all
     */
    public static function clear(?string $type = null): void
    {
        if ($type !== null) {
            self::$registry[$type] = [];
        } else {
            self::$registry = [];
        }
    }
} 
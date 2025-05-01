<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class RelationshipBuilder
{
    /**
     * Relationship definitions
     */
    private array $relationshipDefinitions = [];

    /**
     * Add relationship definitions to be built
     *
     * @param  string  $type  The relationship type (belongsToMany, belongsTo, hasMany)
     * @param  string  $sourceType  Source entity type
     * @param  string  $targetType  Target entity type
     * @param  array  $relationships  Map of source keys to target keys
     * @param  string|null  $foreignKey  Optional foreign key name for belongsTo relationships
     */
    public function addRelationship(
        string $type,
        string $sourceType,
        string $targetType,
        array $relationships,
        ?string $foreignKey = null
    ): self {
        $this->relationshipDefinitions[] = [
            'type' => $type,
            'sourceType' => $sourceType,
            'targetType' => $targetType,
            'relationships' => $relationships,
            'foreignKey' => $foreignKey,
        ];

        return $this;
    }

    /**
     * Build all registered relationships
     */
    public function buildAll(): void
    {
        foreach ($this->relationshipDefinitions as $definition) {
            $method = 'build'.Str::studly($definition['type']);

            if (method_exists($this, $method)) {
                $this->{$method}(
                    $definition['sourceType'],
                    $definition['targetType'],
                    $definition['relationships'],
                    $definition['foreignKey'] ?? null
                );
            } else {
                Log::warning("Unknown relationship type: {$definition['type']}");
            }
        }
    }

    /**
     * Add and build a many-to-many relationship
     */
    public function belongsToMany(string $sourceType, string $targetType, array $relationships): self
    {
        return $this->addRelationship('belongsToMany', $sourceType, $targetType, $relationships);
    }

    /**
     * Add and build a belongs-to relationship
     */
    public function belongsTo(string $sourceType, string $targetType, array $relationships, ?string $foreignKey = null): self
    {
        return $this->addRelationship('belongsTo', $sourceType, $targetType, $relationships, $foreignKey);
    }

    /**
     * Add and build a has-many relationship
     */
    public function hasMany(string $sourceType, string $targetType, array $relationships): self
    {
        return $this->addRelationship('hasMany', $sourceType, $targetType, $relationships);
    }

    /**
     * Build belongs-to-many relationships between entities
     *
     * @param  string  $sourceType  Source entity type
     * @param  string  $targetType  Target entity type
     * @param  array<string, array<string>>  $relationships  Map of source keys to target keys
     */
    private function buildBelongsToMany(
        string $sourceType,
        string $targetType,
        array $relationships
    ): void {
        foreach ($relationships as $sourceKey => $targetKeys) {
            $source = FixtureRegistry::get($sourceType, $sourceKey);

            if (! $source instanceof \Illuminate\Database\Eloquent\Model) {
                Log::warning("Source entity not found for relationship: {$sourceType}.{$sourceKey}");

                continue;
            }

            foreach ($targetKeys as $targetKey) {
                $target = FixtureRegistry::get($targetType, $targetKey);

                if (! $target instanceof \Illuminate\Database\Eloquent\Model) {
                    Log::warning("Target entity not found for relationship: {$targetType}.{$targetKey}");

                    continue;
                }

                try {
                    $source->{$targetType}()->attach($target->id);
                } catch (\Exception $e) {
                    Log::error("Failed to build relationship {$sourceType}.{$sourceKey} -> {$targetType}.{$targetKey}: ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Build belongs-to relationship between entities
     *
     * @param  string  $sourceType  Source entity type
     * @param  string  $targetType  Target entity type
     * @param  array<string, string>  $relationships  Map of source keys to target keys
     * @param  string|null  $foreignKey  Foreign key to use (defaults to "{$targetType}_id")
     */
    private function buildBelongsTo(
        string $sourceType,
        string $targetType,
        array $relationships,
        ?string $foreignKey = null
    ): void {
        $foreignKey ??= "{$targetType}_id";

        foreach ($relationships as $sourceKey => $targetKey) {
            $source = FixtureRegistry::get($sourceType, $sourceKey);
            $target = FixtureRegistry::get($targetType, $targetKey);

            if (! $source instanceof \Illuminate\Database\Eloquent\Model || ! $target instanceof \Illuminate\Database\Eloquent\Model) {
                Log::warning("Entity not found for relationship: {$sourceType}.{$sourceKey} -> {$targetType}.{$targetKey}");

                continue;
            }

            try {
                $source->update([$foreignKey => $target->id]);
            } catch (\Exception $e) {
                Log::error("Failed to build relationship {$sourceType}.{$sourceKey} -> {$targetType}.{$targetKey}: ".$e->getMessage());
            }
        }
    }

    /**
     * Build has-many relationship between entities
     *
     * @param  string  $sourceType  Source entity type
     * @param  string  $targetType  Target entity type
     * @param  array<string, array<string>>  $relationships  Map of source keys to target keys
     */
    private function buildHasMany(
        string $sourceType,
        string $targetType,
        array $relationships
    ): void {
        foreach ($relationships as $sourceKey => $targetKeys) {
            $source = FixtureRegistry::get($sourceType, $sourceKey);

            if (! $source instanceof \Illuminate\Database\Eloquent\Model) {
                Log::warning("Source entity not found for relationship: {$sourceType}.{$sourceKey}");

                continue;
            }

            $derivedForeignKey = Str::singular($sourceType).'_id';

            foreach ($targetKeys as $targetKey) {
                $target = FixtureRegistry::get($targetType, $targetKey);

                if (! $target instanceof \Illuminate\Database\Eloquent\Model) {
                    Log::warning("Target entity not found for relationship: {$targetType}.{$targetKey}");

                    continue;
                }

                try {
                    // For has-many, we update the child to point to the parent
                    $target->update([$derivedForeignKey => $source->id]);
                } catch (\Exception $e) {
                    Log::error("Failed to build relationship {$sourceType}.{$sourceKey} -> {$targetType}.{$targetKey}: ".$e->getMessage());
                }
            }
        }
    }
}

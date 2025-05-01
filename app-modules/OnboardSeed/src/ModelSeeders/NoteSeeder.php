<?php

declare(strict_types=1);

namespace Relaticle\OnboardSeed\ModelSeeders;

use App\Enums\CustomFields\Note as NoteCustomField;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Relaticle\OnboardSeed\Support\BaseModelSeeder;
use Relaticle\OnboardSeed\Support\FixtureRegistry;

final class NoteSeeder extends BaseModelSeeder
{
    protected string $modelClass = Note::class;

    protected string $entityType = 'notes';

    /**
     * Map of singular entity types to their plural registry keys
     */
    private array $entityTypeMap = [
        'company' => 'companies',
        'person' => 'people',
        'opportunity' => 'opportunities',
        'task' => 'tasks',
        'note' => 'notes',
    ];

    protected array $fieldCodes = [
        NoteCustomField::BODY->value,
    ];

    /**
     * Create note entities from fixtures
     *
     * @param  Team  $team  The team to create data for
     * @param  Authenticatable  $user  The user creating the data
     * @param  array<string, mixed>  $context  Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function createEntitiesFromFixtures(Team $team, Authenticatable $user, array $context = []): array
    {
        $fixtures = $this->loadEntityFixtures();
        $notes = [];

        foreach ($fixtures as $key => $data) {
            $noteableType = $data['noteable_type'] ?? null;
            $noteableKey = $data['noteable_key'] ?? null;

            if (! $noteableType || ! $noteableKey) {
                Log::warning("Missing noteable reference for note: {$key}");

                continue;
            }

            // Map the singular entity type to its plural registry key
            $registryKey = $this->getPluralEntityType($noteableType);
            $noteable = FixtureRegistry::get($registryKey, $noteableKey);

            if (! $noteable instanceof \Illuminate\Database\Eloquent\Model) {
                Log::warning("Noteable entity not found for note: {$key}, entity type: {$noteableType} (registry key: {$registryKey}), key: {$noteableKey}");

                continue;
            }

            $note = $this->createNoteFromFixture($noteable, $user, $key, $data);
            $notes[$key] = $note;
        }

        return [
            'notes' => $notes,
        ];
    }

    /**
     * Get the plural registry key for an entity type
     */
    private function getPluralEntityType(string $singularType): string
    {
        return $this->entityTypeMap[$singularType] ?? Str::plural($singularType);
    }

    /**
     * Create a note from fixture data
     */
    private function createNoteFromFixture(
        Model $noteable,
        Authenticatable $user,
        string $key,
        array $data
    ): Note {
        $attributes = [
            'title' => $data['title'],
            'team_id' => $user->currentTeam->id,
        ];

        $customFields = $data['custom_fields'] ?? [];

        $note = $noteable->notes()->create([
            ...$attributes,
            'creator_id' => $user->id,
            ...$this->getGlobalAttributes(),
        ]);

        $this->applyCustomFields($note, $customFields);

        // Register the note in the registry
        FixtureRegistry::register($this->entityType, $key, $note);

        return $note;
    }
}

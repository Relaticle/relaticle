<?php

declare(strict_types=1);

namespace Relaticle\ImportWizard\Services;

use App\Models\Company;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\ImportWizard\Filament\Imports\CompanyImporter;
use Relaticle\ImportWizard\Filament\Imports\NoteImporter;
use Relaticle\ImportWizard\Filament\Imports\OpportunityImporter;
use Relaticle\ImportWizard\Filament\Imports\PeopleImporter;
use Relaticle\ImportWizard\Filament\Imports\TaskImporter;

/**
 * Fast record resolution for import previews using in-memory caching.
 *
 * Follows the CompanyMatcher pattern: pre-load all records in bulk queries,
 * then use O(1) hash table lookups instead of per-row database queries.
 *
 * Performance: Reduces 10,000 queries to 3-5 queries for 10,000 row previews.
 */
final class ImportRecordResolver
{
    /**
     * In-memory cache of records indexed for O(1) lookups.
     *
     * @var array<string, array<string, array<string, Model>>>
     */
    private array $cache = [
        'people' => ['byId' => [], 'byEmail' => []],
        'companies' => ['byId' => [], 'byName' => []],
        'opportunities' => ['byId' => [], 'byName' => []],
        'tasks' => ['byId' => [], 'byTitle' => []],
        'notes' => ['byId' => [], 'byTitle' => []],
    ];

    private ?string $cachedTeamId = null;

    /**
     * Preload all records for a team based on importer class.
     *
     * @param  class-string  $importerClass
     */
    public function loadForTeam(string $teamId, string $importerClass): void
    {
        // Skip if already loaded for this team
        if ($this->cachedTeamId === $teamId) {
            return;
        }

        $this->cachedTeamId = $teamId;
        $this->cache = [
            'people' => ['byId' => [], 'byEmail' => []],
            'companies' => ['byId' => [], 'byName' => []],
            'opportunities' => ['byId' => [], 'byName' => []],
            'tasks' => ['byId' => [], 'byTitle' => []],
            'notes' => ['byId' => [], 'byTitle' => []],
        ];

        // Load records based on importer type
        match ($importerClass) {
            PeopleImporter::class => $this->loadPeople($teamId),
            CompanyImporter::class => $this->loadCompanies($teamId),
            OpportunityImporter::class => $this->loadOpportunities($teamId),
            TaskImporter::class => $this->loadTasks($teamId),
            NoteImporter::class => $this->loadNotes($teamId),
            default => null,
        };
    }

    /**
     * Resolve any record by ID across all cached entity types.
     *
     * @param  class-string  $importerClass
     */
    public function resolveById(string $id, string $teamId, string $importerClass): ?Model
    {
        $this->ensureCacheLoaded($teamId);

        $cacheKey = match ($importerClass) {
            PeopleImporter::class => 'people',
            CompanyImporter::class => 'companies',
            OpportunityImporter::class => 'opportunities',
            TaskImporter::class => 'tasks',
            NoteImporter::class => 'notes',
            default => null,
        };

        if ($cacheKey === null) {
            return null;
        }

        return $this->cache[$cacheKey]['byId'][$id] ?? null;
    }

    /**
     * Resolve a People record by email addresses.
     *
     * @param  array<string>  $emails
     */
    public function resolvePersonByEmail(array $emails, string $teamId): ?People
    {
        $this->ensureCacheLoaded($teamId);

        foreach ($emails as $email) {
            $email = strtolower(trim($email));
            if (isset($this->cache['people']['byEmail'][$email])) {
                /** @var People */
                return $this->cache['people']['byEmail'][$email];
            }
        }

        return null;
    }

    /**
     * Resolve a Task record by title.
     */
    public function resolveTaskByTitle(string $title, string $teamId): ?Task
    {
        $this->ensureCacheLoaded($teamId);

        $title = trim($title);

        /** @var Task|null */
        return $this->cache['tasks']['byTitle'][$title] ?? null;
    }

    /**
     * Resolve a Note record by title.
     */
    public function resolveNoteByTitle(string $title, string $teamId): ?Note
    {
        $this->ensureCacheLoaded($teamId);

        $title = trim($title);

        /** @var Note|null */
        return $this->cache['notes']['byTitle'][$title] ?? null;
    }

    /**
     * Resolve a Company record by name.
     */
    public function resolveCompanyByName(string $name, string $teamId): ?Company
    {
        $this->ensureCacheLoaded($teamId);

        $name = trim($name);

        return $this->cache['companies']['byName'][$name] ?? null;
    }

    /**
     * Resolve an Opportunity record by name.
     */
    public function resolveOpportunityByName(string $name, string $teamId): ?Opportunity
    {
        $this->ensureCacheLoaded($teamId);

        $name = trim($name);

        return $this->cache['opportunities']['byName'][$name] ?? null;
    }

    /**
     * Load all people for a team with email custom field values.
     */
    private function loadPeople(string $teamId): void
    {
        // Query 1: Get emails custom field ID
        $emailsField = CustomField::withoutGlobalScopes()
            ->where('code', 'emails')
            ->where('entity_type', People::class)
            ->where('tenant_id', $teamId)
            ->first();

        if (! $emailsField) {
            return;
        }

        // Query 2: Load ALL people with email custom field values
        $people = People::query()
            ->where('team_id', $teamId)
            ->with(['customFieldValues' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($emailsField): void {
                $query->withoutGlobalScopes()
                    ->where('custom_field_id', $emailsField->id);
            }])
            ->get();

        // Build indexes
        foreach ($people as $person) {
            // Index by ID (cast to string to match array type)
            $this->cache['people']['byId'][(string) $person->id] = $person;

            // Index by each email (lowercase for case-insensitive matching)
            $emailCustomFieldValue = $person->customFieldValues->first();
            if ($emailCustomFieldValue) {
                $emails = $emailCustomFieldValue->json_value ?? [];
                foreach ($emails as $email) {
                    $email = strtolower(trim((string) $email));
                    // First match wins (same as current behavior)
                    if ($email !== '' && ! isset($this->cache['people']['byEmail'][$email])) {
                        $this->cache['people']['byEmail'][$email] = $person;
                    }
                }
            }
        }
    }

    /**
     * Load all companies for a team.
     */
    private function loadCompanies(string $teamId): void
    {
        // Query: Load ALL companies
        $companies = Company::query()
            ->where('team_id', $teamId)
            ->get();

        // Build indexes
        foreach ($companies as $company) {
            // Index by ID (cast to string to match array type)
            $this->cache['companies']['byId'][(string) $company->id] = $company;

            // Index by name (exact match)
            $name = trim($company->name);
            // First match wins (same as current behavior)
            if ($name !== '' && ! isset($this->cache['companies']['byName'][$name])) {
                $this->cache['companies']['byName'][$name] = $company;
            }
        }
    }

    /**
     * Load all opportunities for a team.
     */
    private function loadOpportunities(string $teamId): void
    {
        // Query: Load ALL opportunities
        $opportunities = Opportunity::query()
            ->where('team_id', $teamId)
            ->get();

        // Build indexes
        foreach ($opportunities as $opportunity) {
            // Index by ID (cast to string to match array type)
            $this->cache['opportunities']['byId'][(string) $opportunity->id] = $opportunity;

            // Index by name (exact match)
            $name = trim((string) $opportunity->name);
            // First match wins (same as current behavior)
            if ($name !== '' && ! isset($this->cache['opportunities']['byName'][$name])) {
                $this->cache['opportunities']['byName'][$name] = $opportunity;
            }
        }
    }

    /**
     * Load all tasks for a team.
     */
    private function loadTasks(string $teamId): void
    {
        $tasks = Task::query()
            ->where('team_id', $teamId)
            ->get();

        foreach ($tasks as $task) {
            $this->cache['tasks']['byId'][(string) $task->id] = $task;

            $title = trim((string) $task->title);
            if ($title !== '' && ! isset($this->cache['tasks']['byTitle'][$title])) {
                $this->cache['tasks']['byTitle'][$title] = $task;
            }
        }
    }

    /**
     * Load all notes for a team.
     */
    private function loadNotes(string $teamId): void
    {
        $notes = Note::query()
            ->where('team_id', $teamId)
            ->get();

        foreach ($notes as $note) {
            $this->cache['notes']['byId'][(string) $note->id] = $note;

            $title = trim((string) $note->title);
            if ($title !== '' && ! isset($this->cache['notes']['byTitle'][$title])) {
                $this->cache['notes']['byTitle'][$title] = $note;
            }
        }
    }

    /**
     * Ensure cache is loaded for the team.
     */
    private function ensureCacheLoaded(string $teamId): void
    {
        if ($this->cachedTeamId !== $teamId) {
            throw new \RuntimeException(
                "ImportRecordResolver not loaded for team {$teamId}. Call loadForTeam() first."
            );
        }
    }
}

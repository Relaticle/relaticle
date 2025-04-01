<?php

declare(strict_types=1);

namespace Database\Seeders\SampleData\ModelSeeders;

use App\Enums\CustomFields\Note as NoteCustomField;
use App\Models\Note;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\SampleData\Support\BaseModelSeeder;
use Illuminate\Database\Eloquent\Model;

class NoteSeeder extends BaseModelSeeder
{
    protected string $modelClass = Note::class;
    
    protected array $fieldCodes = [
        NoteCustomField::BODY->value,
    ];
    
    /**
     * Seed model implementation
     * 
     * @param Team $team The team to create data for
     * @param User $user The user creating the data
     * @param array<string, mixed> $context Context data from previous seeders
     * @return array<string, mixed> Seeded data for use by subsequent seeders
     */
    protected function seedModel(Team $team, User $user, array $context = []): array
    {
        $companies = $context['companies'] ?? [];
        $people = $context['people'] ?? [];
        
        if (empty($companies) || empty($people)) {
            return ['notes' => []];
        }
        
        $notes = [];
        
        $notes['acme_note'] = $this->createNote(
            $companies['acme'],
            $user,
            'Initial meeting summary',
            '<p>Initial meeting went well. They\'re interested in our services and would like a proposal by next week. Key decision makers are Jane and John.</p>'
        );
        
        $notes['technova_note'] = $this->createNote(
            $companies['technova'],
            $user,
            'Integration project discussion',
            '<p>TechNova is looking for a custom integration solution. They need it completed within 3 months. Sarah will be our main technical contact.</p>'
        );
        
        $notes['jane_note'] = $this->createNote(
            $people['jane'],
            $user,
            'Jane contact information',
            '<p>Jane is the primary decision maker. She prefers communication via email rather than phone calls. She has final budget approval.</p>'
        );
        
        return ['notes' => $notes];
    }
    
    /**
     * Create a note with custom fields
     *
     * @param Model $noteable The model to attach the note to
     * @param User $user The user creating the note
     * @param string $title The note title
     * @param string $body The note body content
     * @return Note The created note
     */
    private function createNote(Model $noteable, User $user, string $title, string $body): Note
    {
        $note = $noteable->notes()->create([
            'title' => $title,
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
        ]);
        
        if (isset($this->fields[NoteCustomField::BODY->value])) {
            $note->saveCustomFieldValue(
                $this->fields[NoteCustomField::BODY->value],
                $body
            );
        }
        
        return $note;
    }
} 
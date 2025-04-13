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

        $notes['figma_note'] = $this->createNote(
            $companies['figma'],
            $user,
            'Initial meeting summary',
            '<p>Initial meeting with Figma went well. They\'re interested in our enterprise solutions and would like a proposal by next week. Key decision maker is Dylan.</p>'
        );

        $notes['apple_note'] = $this->createNote(
            $companies['apple'],
            $user,
            'Developer partnership discussion',
            '<p>Apple is interested in exploring a partnership for their developer ecosystem. They need a comprehensive solution within 3 months. Tim will be our main contact.</p>'
        );

        $notes['airbnb_note'] = $this->createNote(
            $companies['airbnb'],
            $user,
            'Hospitality platform integration',
            '<p>Airbnb is looking to enhance their host tools with our analytics solution. Brian expressed interest in a custom dashboard for hosts to better understand guest preferences.</p>'
        );

        $notes['notion_note'] = $this->createNote(
            $companies['notion'],
            $user,
            'API integration possibilities',
            '<p>Notion team is interested in exploring integration opportunities. They\'re looking for ways to connect their platform with our services. Ivan will be our primary contact for technical discussions.</p>'
        );

        $notes['dylan_note'] = $this->createNote(
            $people['dylan'],
            $user,
            'Dylan contact information',
            '<p>Dylan is the CEO and primary decision maker. He prefers communication via email rather than phone calls. He has final budget approval for the enterprise plan.</p>'
        );

        $notes['tim_note'] = $this->createNote(
            $people['tim'],
            $user,
            'Tim meeting insights',
            '<p>Tim showed great interest in our developer tools. He wants to integrate our solution across the entire Apple developer ecosystem. He prefers weekly progress updates via email.</p>'
        );

        $notes['brian_note'] = $this->createNote(
            $people['brian'],
            $user,
            'Brian requirements discussion',
            '<p>Brian outlined specific requirements for the host analytics platform. He emphasized the importance of a clean UI and mobile-first approach. He suggested a follow-up meeting with their product team next week.</p>'
        );

        $notes['ivan_note'] = $this->createNote(
            $people['ivan'],
            $user,
            'Ivan meeting notes',
            '<p>Ivan shared their roadmap for API improvements. He\'s particularly interested in our data synchronization capabilities and wants to explore how we can build a seamless integration between our platforms.</p>'
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
            'creator_id' => $user->id,
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

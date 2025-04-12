<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Note;

class NoteObserver
{
    public function creating(Note $note): void
    {
        if (auth()->check()) {
            $note->creator_id = auth()->id();
            $note->team_id = auth()->user()->currentTeam->getKey();
        }
    }
}

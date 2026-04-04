<?php

declare(strict_types=1);

namespace App\Livewire\App\Teams;

use App\Actions\Jetstream\CancelTeamDeletion;
use App\Actions\Jetstream\ScheduleTeamDeletion;
use App\Livewire\BaseLivewireComponent;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class DeleteTeam extends BaseLivewireComponent
{
    public Team $team;

    public function mount(Team $team): void
    {
        $this->team = $team;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('teams.sections.delete_team.title'))
                    ->description(__('teams.sections.delete_team.description'))
                    ->aside()
                    ->visible(fn () => Gate::check('delete', $this->team))
                    ->schema([
                        TextEntry::make('notice')
                            ->hiddenLabel()
                            ->state(fn (): string => $this->team->isScheduledForDeletion()
                                ? "This team is scheduled for deletion on {$this->team->scheduled_deletion_at->format('F j, Y')}."
                                : __('teams.sections.delete_team.notice')),
                        Actions::make([
                            Action::make('deleteAccountAction')
                                ->label(__('teams.actions.delete_team'))
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading(__('teams.sections.delete_team.title'))
                                ->modalDescription(__('teams.modals.delete_team.notice'))
                                ->modalSubmitActionLabel(__('teams.actions.delete_team'))
                                ->modalCancelAction(false)
                                ->visible(fn (): bool => ! $this->team->isScheduledForDeletion())
                                ->action(fn () => $this->deleteTeam($this->team)),
                            Action::make('cancelDeletionAction')
                                ->label('Cancel Deletion')
                                ->color('gray')
                                ->requiresConfirmation()
                                ->modalHeading('Cancel team deletion?')
                                ->modalDescription('The team and all its data will be preserved.')
                                ->visible(fn (): bool => $this->team->isScheduledForDeletion())
                                ->action(fn () => $this->cancelTeamDeletion($this->team)),
                        ]),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.app.teams.delete-team');
    }

    public function deleteTeam(Team $team): void
    {
        try {
            resolve(ScheduleTeamDeletion::class)->schedule($this->authUser(), $team);

            $this->sendNotification("Team scheduled for deletion on {$team->refresh()->scheduled_deletion_at->format('F j, Y')}");
        } catch (ValidationException $e) {
            $this->addError('team', $e->validator->errors()->first());
        }
    }

    public function cancelTeamDeletion(Team $team): void
    {
        resolve(CancelTeamDeletion::class)->cancel($this->authUser(), $team);

        $this->sendNotification('Team deletion cancelled');
    }
}

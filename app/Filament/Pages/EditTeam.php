<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

final class EditTeam extends EditTenantProfile
{
    //    protected string $view = 'filament.pages.edit-team';

    protected static ?string $slug = 'team';

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return 'Team Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Team Owner'))
                    ->description(__('The team\'s name and owner information.'))
                    ->aside()
                    ->schema([
                        TextInput::make('name'),
                    ])
                    ->footerActionsAlignment(Alignment::End)
                    ->footerActions([
                        Action::make('save')
                            ->label(__('Save'))
                            ->color('primary')
                            ->action(fn () => $this->save()), // Define the save method in your class
                    ]),

                Section::make(__('Add Team Member'))
                    ->description(__('Add a new team member to your team, allowing them to collaborate with you.'))
                    ->aside()
                    ->schema([
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->placeholder(__('Enter the email address of the new team member')),
                    ])
                    ->footerActions([
                        Action::make('add_member')
                            ->label(__('Add Member'))
                            ->color('primary')
                            ->icon('heroicon-o-user-plus')
                            ->requiresConfirmation()
                            ->action(fn () => $this->addMember()), // Define the addMember method in your class
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [];
    }
}

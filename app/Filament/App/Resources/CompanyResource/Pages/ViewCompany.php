<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CompanyResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use App\Filament\App\Resources\CompanyResource\RelationManagers;
use App\Filament\Components\Infolists\AvatarName;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;

final class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Split::make([
                    Section::make([
                        Split::make([
                            AvatarName::make('logo')
                                ->avatar('logo')
                                ->name('name')
                                ->avatarSize('lg')
                                ->textSize('xl')
                                ->square()
                                ->label(''),
                            AvatarName::make('creator')
                                ->avatar('creator.avatar')
                                ->name('creator.name')
                                ->avatarSize('md')
                                ->textSize('sm')  // Default text size for creator
                                ->circular()
                                ->label('Created By'),
                            AvatarName::make('accountOwner')
                                ->avatar('accountOwner.avatar')
                                ->name('accountOwner.name')
                                ->avatarSize('sm')
                                ->textSize('sm')  // Default text size for account owner
                                ->circular()
                                ->label('Account Owner'),
                        ]),
                        CustomFieldsInfolists::make(),
                    ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->label('Created Date')
                            ->icon('heroicon-o-clock')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->icon('heroicon-o-clock')
                            ->dateTime(),
                    ])->grow(false),
                ])->columnSpan('full'),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            RelationManagers\PeopleRelationManager::class,
            RelationManagers\TasksRelationManager::class,
            RelationManagers\NotesRelationManager::class,
        ];
    }
}

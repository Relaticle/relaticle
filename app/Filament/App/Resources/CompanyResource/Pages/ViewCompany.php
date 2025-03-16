<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CompanyResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use App\Filament\App\Resources\CompanyResource\RelationManagers;
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
                        Infolists\Components\ImageEntry::make('logo')
                                ->label('')
                                ->height(40)
                                ->square(),
                            Infolists\Components\TextEntry::make('name')
                                ->size(TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('accountOwner.name')
                                ->label('Account Owner'),
                        ]),
                        CustomFieldsInfolists::make(),
                    ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
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

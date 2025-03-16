<?php

namespace App\Filament\App\Resources\CompanyResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;
use App\Filament\App\Resources\CompanyResource\RelationManagers;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;

class ViewCompany extends ViewRecord
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
                        Infolists\Components\TextEntry::make('name'),
                        CustomFieldsInfolists::make()
                    ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->grow(false)
                ])->columnSpan('full')
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

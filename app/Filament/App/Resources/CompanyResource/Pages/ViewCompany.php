<?php

namespace App\Filament\App\Resources\CompanyResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;
use App\Filament\App\Resources\CompanyResource\RelationManagers;

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
                Components\Section::make()->schema([
                    Infolists\Components\TextEntry::make('name'),
                    CustomFieldsInfolists::make()->columnSpanFull(),
                ]),
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

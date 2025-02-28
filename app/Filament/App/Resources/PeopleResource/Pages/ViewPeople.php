<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;

final class ViewPeople extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    #[\Override]
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Section::make()->schema([
                Components\TextEntry::make('name'),
                CustomFieldsInfolists::make()->columnSpanFull(),
            ]),
        ]);
    }
}

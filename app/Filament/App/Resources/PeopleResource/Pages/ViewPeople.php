<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;

final class ViewPeople extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

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
        return $infolist->schema([
            Components\Section::make()->schema([
                Split::make([
                    Components\ImageEntry::make('avatar')
                        ->label('')
                        ->height(30)
                        ->circular()
                        ->grow(false),
                    Components\TextEntry::make('name')
                    ->label('')
                        ->size(Components\TextEntry\TextEntrySize::Large),
                    Components\TextEntry::make('company.name')
                        ->label('Company')
                        ->color('primary')
                        ->url(fn($record) => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null),
                ]),
                CustomFieldsInfolists::make()->columnSpanFull(),
            ]),
        ]);
    }
}

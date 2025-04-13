<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use App\Filament\App\Resources\CompanyResource;
use App\Filament\App\Resources\OpportunityResource;
use App\Filament\App\Resources\PeopleResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;

final class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

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
                    Split::make([
                        Infolists\Components\TextEntry::make('name')->grow(true),
                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company')
                            ->color('primary')
                            ->url(fn($record) => $record->company? CompanyResource::getUrl('view', [$record->company]) : null)
                            ->grow(false),
                        Infolists\Components\TextEntry::make('contact.name')
                            ->label('Point of Contact')
                            ->color('primary')
                            ->url(fn($record) => $record->contact ? PeopleResource::getUrl('view', [$record->contact]) : null)
                            ->grow(false),
                    ]),
                    CustomFieldsInfolists::make()->columnSpanFull(),
                ]),
            ]);
    }
}

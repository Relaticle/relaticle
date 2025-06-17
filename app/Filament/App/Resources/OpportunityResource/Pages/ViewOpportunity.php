<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Flex;
use Filament\Infolists\Components\TextEntry;
use App\Filament\App\Resources\CompanyResource;
use App\Filament\App\Resources\OpportunityResource;
use App\Filament\App\Resources\PeopleResource;
use App\Models\Opportunity;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;

final class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    Flex::make([
                        TextEntry::make('name')->grow(true),
                        TextEntry::make('company.name')
                            ->label('Company')
                            ->color('primary')
                            ->url(fn (Opportunity $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null)
                            ->grow(false),
                        TextEntry::make('contact.name')
                            ->label('Point of Contact')
                            ->color('primary')
                            ->url(fn (Opportunity $record): ?string => $record->contact ? PeopleResource::getUrl('view', [$record->contact]) : null)
                            ->grow(false),
                    ]),
                    CustomFieldsInfolists::make()->columnSpanFull(),
                ]),
            ]);
    }
}

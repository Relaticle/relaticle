<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PeopleResource\Pages;

use App\Filament\App\Resources\PeopleResource;
use App\Models\People;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Relaticle\Admin\Filament\Resources\CompanyResource;
use Relaticle\CustomFields\Filament\Infolists\CustomFieldsInfolists;

final class ViewPeople extends ViewRecord
{
    protected static string $resource = PeopleResource::class;

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
        return $schema->schema([
            Section::make()->schema([
                Flex::make([
                    ImageEntry::make('avatar')
                        ->label('')
                        ->height(30)
                        ->circular()
                        ->grow(false),
                    TextEntry::make('name')
                        ->label('')
                        ->size(TextSize::Large),
                    TextEntry::make('company.name')
                        ->label('Company')
                        ->color('primary')
                        ->url(fn (People $record): ?string => $record->company ? CompanyResource::getUrl('view', [$record->company]) : null),
                ]),
                CustomFieldsInfolists::make()->columnSpanFull(),
            ]),
        ]);
    }
}

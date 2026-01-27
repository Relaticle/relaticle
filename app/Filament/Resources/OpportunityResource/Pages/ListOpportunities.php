<?php

declare(strict_types=1);

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Exports\OpportunityExporter;
use App\Filament\Resources\OpportunityResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;
use Relaticle\ImportWizard\Filament\Pages\ImportOpportunities;

final class ListOpportunities extends ListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = OpportunityResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('import')
                    ->label('Import opportunities')
                    ->icon('phosphor-o-upload')
                    ->url(ImportOpportunities::getUrl()),
                ExportAction::make()->exporter(OpportunityExporter::class),
            ])
                ->icon('phosphor-o-arrows-down-up')
                ->color('gray')
                ->button()
                ->label('Import / Export')
                ->size(Size::Small),
            CreateAction::make()->icon('phosphor-o-plus')->size(Size::Small),
        ];
    }
}

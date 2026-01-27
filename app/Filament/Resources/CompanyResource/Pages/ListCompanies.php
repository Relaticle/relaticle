<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Exports\CompanyExporter;
use App\Filament\Resources\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;
use Relaticle\ImportWizard\Filament\Pages\ImportCompanies;

final class ListCompanies extends ListRecords
{
    use InteractsWithCustomFields;

    /** @var class-string<CompanyResource> */
    protected static string $resource = CompanyResource::class;

    /**
     * Get the actions available on the resource index header.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('import')
                    ->label('Import companies')
                    ->icon('phosphor-o-upload')
                    ->url(ImportCompanies::getUrl()),
                ExportAction::make()->exporter(CompanyExporter::class),
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

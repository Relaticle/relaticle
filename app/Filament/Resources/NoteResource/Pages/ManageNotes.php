<?php

declare(strict_types=1);

namespace App\Filament\Resources\NoteResource\Pages;

use App\Filament\Exports\NoteExporter;
use App\Filament\Resources\NoteResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Size;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;
use Relaticle\ImportWizard\Filament\Pages\ImportNotes;

final class ManageNotes extends ManageRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = NoteResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('import')
                    ->label('Import notes')
                    ->icon('phosphor-o-upload')
                    ->url(ImportNotes::getUrl()),
                ExportAction::make()->exporter(NoteExporter::class),
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

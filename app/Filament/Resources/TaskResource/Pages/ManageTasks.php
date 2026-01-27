<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Exports\TaskExporter;
use App\Filament\Resources\TaskResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Size;
use Override;
use Relaticle\CustomFields\Concerns\InteractsWithCustomFields;
use Relaticle\ImportWizard\Filament\Pages\ImportTasks;

final class ManageTasks extends ManageRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = TaskResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('import')
                    ->label('Import tasks')
                    ->icon('phosphor-o-upload')
                    ->url(ImportTasks::getUrl()),
                ExportAction::make()->exporter(TaskExporter::class),
            ])
                ->icon('phosphor-o-arrows-down-up')
                ->color('gray')
                ->button()
                ->label('Import / Export')
                ->size(Size::Small),
            CreateAction::make()->icon('phosphor-o-plus')->size(Size::Small)->slideOver(),
        ];
    }
}

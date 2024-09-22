<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class CustomFieldsNext extends Page
{
    protected static ?string $navigationIcon = 'heroicon-m-document-text';

    protected static string $view = 'filament.pages.custom-fields-next';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    #[Computed]
    public function sections()
    {
        return Collection::make([
            [
                'id' => 1,
                'name' => 'Default Section',
                'fields' => [
                    'Title',
                    'Featured Image',
                    'Article Content',
                ]
            ],
            [
                'id' => 2,
                'name' => 'Another Section',
                'fields' => [
                    'First Name',
                    'Last Name',
                    'Email',
                    'Phone',
                ]
            ]
        ]);
    }

    public function createFieldAction(): Action
    {
        return Action::make('createField')
            ->size(ActionSize::ExtraSmall)
            ->label('Create Field')
            ->action(fn () => dump('create field'));
    }
}

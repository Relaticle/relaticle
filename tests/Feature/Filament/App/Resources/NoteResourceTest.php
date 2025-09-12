<?php

declare(strict_types=1);

use function Pest\Livewire\livewire;

beforeEach(function () {
    //
});

it('can render the index page', function (): void {
    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertCanRenderTableColumn($column);
})->with(['title', 'companies.name', 'people.name', 'creator.name', 'created_at']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['deleted_at', 'updated_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertTableColumnExists($column);
})->with(['title', 'companies.name', 'people.name', 'creator.name', 'deleted_at', 'created_at', 'updated_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertTableColumnVisible($column);
})->with(['title', 'companies.name', 'people.name', 'creator.name', 'deleted_at', 'created_at', 'updated_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\Note::factory(3)->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)->value
        : $column;

    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['companies.name', 'people.name', 'creator.name', 'deleted_at', 'created_at', 'updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\Note::factory(3)->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) !== $search));
})->with(['title', 'creator.name']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\Note::factory()->count(4)->create();
    $trashedRecords = App\Models\Note::factory()->trashed()->count(6)->create();

    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\Note::factory(20)->create();

    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('can bulk delete records', function (): void {
    $records = App\Models\Note::factory(5)->create();

    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertCanSeeTableRecords($records)
        ->selectTableRecords($records)
        ->callAction(Filament\Actions\Testing\TestAction::make(Filament\Actions\DeleteBulkAction::class)->table()->bulk())
        ->assertNotified()
        ->assertCanNotSeeTableRecords($records);

    $this->assertSoftDeleted($records);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\NoteResource\Pages\ManageNotes::class)
        ->assertTableFilterExists($filter);
})->with(['creation_source', 'trashed']);

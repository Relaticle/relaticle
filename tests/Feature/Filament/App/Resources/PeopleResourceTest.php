<?php

declare(strict_types=1);

use function Pest\Livewire\livewire;

beforeEach(function () {
    //
});

it('can render the index page', function (): void {
    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = App\Models\People::factory()->create();

    livewire(App\Filament\Resources\PeopleResource\Pages\ViewPeople::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertCanRenderTableColumn($column);
})->with(['avatar', 'name', 'company.name', 'creator.name']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['created_at', 'updated_at', 'deleted_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertTableColumnExists($column);
})->with(['avatar', 'name', 'company.name', 'creator.name', 'created_at', 'updated_at', 'deleted_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertTableColumnVisible($column);
})->with(['avatar', 'name', 'company.name', 'creator.name', 'created_at', 'updated_at', 'deleted_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\People::factory(3)->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)->value
        : $column;

    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['company.name', 'creator.name', 'created_at', 'updated_at', 'deleted_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\People::factory(3)->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) !== $search));
})->with(['name', 'company.name', 'creator.name']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\People::factory()->count(4)->create();
    $trashedRecords = App\Models\People::factory()->trashed()->count(6)->create();

    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\People::factory(20)->create();

    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('can bulk delete records', function (): void {
    $records = App\Models\People::factory(5)->create();

    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertCanSeeTableRecords($records)
        ->selectTableRecords($records)
        ->callAction(Filament\Actions\Testing\TestAction::make(Filament\Actions\DeleteBulkAction::class)->table()->bulk())
        ->assertNotified()
        ->assertCanNotSeeTableRecords($records);

    $this->assertSoftDeleted($records);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\PeopleResource\Pages\ListPeople::class)
        ->assertTableFilterExists($filter);
})->with(['creation_source', 'trashed']);

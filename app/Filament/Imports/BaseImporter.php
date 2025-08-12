<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;

abstract class BaseImporter extends Importer
{
    /**
     * @param  array<string, mixed>  $columnMap
     * @param  array<string, mixed>  $options
     */
    public function __construct(Import $import, array $columnMap, array $options)
    {
        parent::__construct($import, $columnMap, $options);

        // Store team ID on import for consistency
        $import->team_id = Auth::user()->currentTeam?->getKey();
    }
}

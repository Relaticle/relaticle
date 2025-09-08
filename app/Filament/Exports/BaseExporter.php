<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Team;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class BaseExporter extends Exporter
{
    /**
     * @param  array<string, string>  $columnMap
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        Export $export,
        array $columnMap,
        array $options,
    ) {
        parent::__construct($export, $columnMap, $options);

        // Set the team_id on the export record
        if (Auth::check() && Auth::user()->currentTeam) {
            /** @var Team $currentTeam */
            $currentTeam = Auth::user()->currentTeam;
            $export->team_id = $currentTeam->getKey();
        }
    }

    /**
     * Make exports tenant-aware by scoping to the current team
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            /** @var Team $currentTeam */
            $currentTeam = Auth::user()->currentTeam;

            return $query->where('team_id', $currentTeam->getKey());
        }

        return $query;
    }
}

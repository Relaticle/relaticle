<?php

declare(strict_types=1);

namespace App\Listeners;

use Filament\Events\TenantSet;
use Laravel\Jetstream\Features;

final class SwitchTeam
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TenantSet $event): void
    {
        if (Features::hasTeamFeatures()) {
            $user = $event->getUser();

            $team = $event->getTenant();

            $user->switchTeam($team);
        }
    }
}

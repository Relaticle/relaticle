<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Jetstream\Membership as JetstreamMembership;

final class Membership extends JetstreamMembership
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
}

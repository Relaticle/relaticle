<?php

namespace ManukMinasyan\FilamentAttribute\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ManukMinasyan\FilamentAttribute\FilamentAttribute
 */
class FilamentAttribute extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \ManukMinasyan\FilamentAttribute\FilamentAttribute::class;
    }
}

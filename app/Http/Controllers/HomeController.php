<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final readonly class HomeController
{
    public function __invoke()
    {
        return view('home.index');
    }
}

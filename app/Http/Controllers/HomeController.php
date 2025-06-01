<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final readonly class HomeController
{
    public function __invoke(): View
    {
        return view('home.index');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Str;

class DocumentationController
{
    public function __invoke()
    {
        $policyFile = resource_path('markdown/business-guide.md');

        return view('documentation.index', [
            'businessGuide' => Str::markdown(file_get_contents($policyFile)),
        ]);
    }
}

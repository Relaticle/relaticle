<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class DocumentationController
{
    public function __invoke(Request $request, string $type = 'index')
    {
        $validTypes = [
            'index' => [
                'title' => 'Documentation',
                'file' => 'documentation-index.md',
            ],
            'business' => [
                'title' => 'Business Guide',
                'file' => 'business-guide.md',
            ],
            'technical' => [
                'title' => 'Technical Guide',
                'file' => 'technical-guide.md',
            ],
            'quickstart' => [
                'title' => 'Quick Start Guide',
                'file' => 'quick-start-guide.md',
            ],
            'api' => [
                'title' => 'API Documentation',
                'file' => 'api-guide.md',
            ],
        ];

        // Default to index if an invalid type is provided
        if (! array_key_exists($type, $validTypes)) {
            $type = 'index';
        }

        $documentFile = $validTypes[$type]['file'];
        $path = resource_path('markdown/'.$documentFile);

        // Validate the path is within the intended directory
        $realPath = realpath($path);
        $resourcePath = realpath(resource_path('markdown'));

        if ($realPath === '' || $realPath === '0' || $realPath === false || ! str_starts_with($realPath, $resourcePath)) {
            abort(404, 'Document not found');
        }

        $documentContent = file_exists($realPath)
            ? file_get_contents($realPath)
            : '# Document Not Found';

        return view('documentation.index', [
            'documentContent' => Str::markdown($documentContent),
            'currentType' => $type,
            'documentTitle' => $validTypes[$type]['title'],
            'documentTypes' => $validTypes,
        ]);
    }
}

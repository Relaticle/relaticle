<?php

declare(strict_types=1);

namespace Relaticle\Documentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelMarkdown\MarkdownRenderer;

final readonly class DocumentationController
{
    public function __invoke(Request $request, string $type = 'index')
    {
        $validTypes = [
            'index' => [
                'title' => 'Documentation',
                'file' => 'index.md',
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
        $path = $this->getMarkdownPath($documentFile);

        // Validate the path is within the intended directory
        $realPath = realpath($path);
        $resourcePath = realpath($this->getMarkdownBasePath());

        if ($realPath === '' || $realPath === '0' || $realPath === false || ! str_starts_with($realPath, $resourcePath)) {
            abort(404, 'Document not found');
        }

        $documentContent = file_exists($realPath)
            ? file_get_contents($realPath)
            : '# Document Not Found';

        $documentContent = app(MarkdownRenderer::class)
            ->toHtml($documentContent);

        $tableOfContents = $this->extractTableOfContents($documentContent);

        return view('documentation::index', [
            'documentContent' => $documentContent,
            'tableOfContents' => $tableOfContents,
            'currentType' => $type,
            'documentTitle' => $validTypes[$type]['title'],
            'documentTypes' => $validTypes,
        ]);
    }

    private function extractTableOfContents(string $contents)
    {
        $matches = [];

        preg_match_all('/<h2.*><a.*id="([^"]+)".*>#<\/a>([^<]+)/', $contents, $matches);

        $allMatches = array_combine($matches[1], $matches[2]);

        return collect($allMatches)
            ->reject(fn (string $result) => str_contains($result, 'Beatles'))
            ->toArray();
    }

    /**
     * Get the path to the markdown file.
     */
    private function getMarkdownPath(string $file): string
    {
        return $this->getMarkdownBasePath().'/'.$file;
    }

    /**
     * Get the base path for markdown files.
     */
    private function getMarkdownBasePath(): string
    {
        return __DIR__.'/../../../resources/markdown';
    }
}

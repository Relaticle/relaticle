<?php

declare(strict_types=1);

namespace Relaticle\Documentation\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\LaravelMarkdown\MarkdownRenderer;

final readonly class DocumentationController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View|object
     */
    public function index()
    {
        return view('documentation::index');
    }

    /**
     * @param Request $request
     * @param string $type
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View|object
     */
    public function show(string $type = 'index')
    {
        $validTypes = [
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

        abort_if(!array_key_exists($type, $validTypes), 404);

        $documentFile = $validTypes[$type]['file'];
        $path = $this->getMarkdownPath($documentFile);

        // Validate the path is within the intended directory
        $realPath = realpath($path);
        $resourcePath = realpath($this->getMarkdownBasePath());

        if ($realPath === '' || $realPath === '0' || $realPath === false || !str_starts_with($realPath, $resourcePath)) {
            abort(404, 'Document not found');
        }

        $documentContent = file_exists($realPath)
            ? file_get_contents($realPath)
            : '# Document Not Found';

        $documentContent = app(MarkdownRenderer::class)
            ->toHtml($documentContent);

        $tableOfContents = $this->extractTableOfContents($documentContent);

        return view('documentation::show', [
            'content' => $documentContent,
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
            ->reject(fn(string $result) => str_contains($result, 'Beatles'))
            ->toArray();
    }

    /**
     * Get the path to the markdown file.
     */
    private function getMarkdownPath(string $file): string
    {
        return $this->getMarkdownBasePath() . '/' . $file;
    }

    /**
     * Get the base path for markdown files.
     */
    private function getMarkdownBasePath(): string
    {
        return __DIR__ . '/../../../resources/markdown';
    }
}

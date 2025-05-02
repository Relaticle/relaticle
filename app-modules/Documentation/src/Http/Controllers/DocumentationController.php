<?php

declare(strict_types=1);

namespace Relaticle\Documentation\Http\Controllers;

use Illuminate\Http\Request;
use Relaticle\Documentation\Data\DocumentSearchRequest;
use Relaticle\Documentation\Services\DocumentationService;

class DocumentationController
{
    public function __construct(
        private readonly DocumentationService $documentationService
    ) {
    }

    /**
     * Display the documentation index page
     */
    public function index()
    {
        return view('documentation::index', [
            'documentTypes' => $this->documentationService->getAllDocumentTypes(),
        ]);
    }

    /**
     * Display a specific documentation page
     */
    public function show(string $type = 'business')
    {
        $document = $this->documentationService->getDocument($type);
        
        return view('documentation::show', [
            'content' => $document->content,
            'tableOfContents' => $document->tableOfContents,
            'currentType' => $document->type,
            'documentTitle' => $document->title,
            'documentTypes' => $this->documentationService->getAllDocumentTypes(),
        ]);
    }
    
    /**
     * Search documentation
     */
    public function search(Request $request)
    {
        $searchRequest = DocumentSearchRequest::from($request);
        $results = $this->documentationService->search($searchRequest);
        
        if ($request->expectsJson()) {
            return response()->json($results);
        }
        
        return view('documentation::search', [
            'results' => $results,
            'query' => $searchRequest->query,
            'documentTypes' => $this->documentationService->getAllDocumentTypes(),
        ]);
    }
}

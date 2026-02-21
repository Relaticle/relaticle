<?php

declare(strict_types=1);

use App\Scribe\Strategies\GetFromSpatieQueryBuilder;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;

mutates(GetFromSpatieQueryBuilder::class);

function makeStrategy(): GetFromSpatieQueryBuilder
{
    return new GetFromSpatieQueryBuilder(new DocumentationConfig(config('scribe')));
}

function endpointDataFromRoute(string $routeName): ExtractedEndpointData
{
    $route = app('router')->getRoutes()->getByName($routeName);

    $controllerAction = $route->getAction('uses');
    [$controller, $method] = explode('@', $controllerAction);

    return new ExtractedEndpointData([
        'uri' => $route->uri(),
        'httpMethods' => $route->methods(),
        'method' => new ReflectionMethod($controller, $method),
        'route' => $route,
    ]);
}

it('extracts filters from List action classes', function (): void {
    $result = makeStrategy()(endpointDataFromRoute('companies.index'));

    expect($result)
        ->toBeArray()
        ->toHaveKey('filter[name]')
        ->and($result['filter[name]']['type'])->toBe('string')
        ->and($result['filter[name]']['required'])->toBeFalse();
});

it('extracts sort parameters', function (): void {
    $result = makeStrategy()(endpointDataFromRoute('companies.index'));

    expect($result)
        ->toHaveKey('sort')
        ->and($result['sort']['description'])->toContain('name')
        ->and($result['sort']['description'])->toContain('created_at');
});

it('extracts include parameters', function (): void {
    $result = makeStrategy()(endpointDataFromRoute('companies.index'));

    expect($result)
        ->toHaveKey('include')
        ->and($result['include']['description'])->toContain('creator')
        ->and($result['include']['description'])->toContain('people')
        ->and($result['include']['description'])->toContain('opportunities');
});

it('adds pagination parameters', function (): void {
    $result = makeStrategy()(endpointDataFromRoute('companies.index'));

    expect($result)
        ->toHaveKey('per_page')
        ->toHaveKey('cursor')
        ->toHaveKey('page')
        ->and($result['per_page']['type'])->toBe('integer')
        ->and($result['cursor']['type'])->toBe('string')
        ->and($result['page']['type'])->toBe('integer');
});

it('returns null for non-index methods', function (): void {
    $result = makeStrategy()(endpointDataFromRoute('companies.show'));

    expect($result)->toBeNull();
});

it('returns null when controller has no List action parameter', function (): void {
    $route = app('router')->getRoutes()->getByName('companies.index');
    $reflection = new ReflectionMethod(\App\Http\Controllers\Api\V1\CustomFieldsController::class, 'index');

    $endpointData = new ExtractedEndpointData([
        'uri' => $route->uri(),
        'httpMethods' => $route->methods(),
        'method' => $reflection,
        'route' => $route,
    ]);

    expect(makeStrategy()($endpointData))->toBeNull();
});

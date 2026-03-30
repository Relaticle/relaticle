<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
});

describe('API routing - default path mode', function () {
    it('serves API at /api/v1 prefix', function (): void {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/companies')->assertOk();
    });

    it('does not serve API at root /v1 prefix', function (): void {
        Sanctum::actingAs($this->user);

        $this->getJson('/v1/companies')->assertNotFound();
    });
});

describe('API routing - subdomain mode', function () {
    beforeEach(function () {
        putenv('API_DOMAIN=api.example.com');
        $this->refreshApplication();
        $this->user = User::factory()->withPersonalTeam()->create();
    });

    afterEach(function () {
        putenv('API_DOMAIN');
    });

    it('serves root JSON info on API subdomain', function (): void {
        $response = $this->get('http://api.example.com/');
        $response->assertOk();

        $json = $response->json();
        expect($json)->toHaveKeys(['name', 'version', 'docs']);
        expect($json['name'])->toBe('Relaticle API');
        expect($json['version'])->toBe('v1');
    });

    it('serves API resources on subdomain at /v1 prefix', function (): void {
        Sanctum::actingAs($this->user);

        $this->getJson('http://api.example.com/v1/companies')->assertOk();
    });
});

describe('MCP routing - default path mode', function () {
    it('serves MCP at /mcp path', function (): void {
        $this->get('/mcp')->assertStatus(405);
    });
});

describe('MCP routing - subdomain mode', function () {
    beforeEach(function () {
        putenv('MCP_DOMAIN=mcp.example.com');
        $this->refreshApplication();
        $this->user = User::factory()->withPersonalTeam()->create();
    });

    afterEach(function () {
        putenv('MCP_DOMAIN');
    });

    it('returns JSON info on MCP subdomain root', function (): void {
        $response = $this->get('http://mcp.example.com/');
        $response->assertOk();

        $json = $response->json();
        expect($json)->toHaveKeys(['name', 'version', 'docs']);
        expect($json['name'])->toBe('Relaticle MCP Server');
    });
});

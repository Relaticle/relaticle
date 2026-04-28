<?php

declare(strict_types=1);

it('exposes the OAuth protected-resource discovery document', function (): void {
    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonStructure(['resource', 'authorization_servers']);
});

it('exposes the OAuth authorization-server discovery document', function (): void {
    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'registration_endpoint',
            'response_types_supported',
            'grant_types_supported',
            'code_challenge_methods_supported',
        ])
        ->assertJsonFragment(['code_challenge_methods_supported' => ['S256']]);
});

it('accepts dynamic client registration', function (): void {
    $response = $this->postJson('/oauth/register', [
        'client_name' => 'Test Directory Client',
        'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        'grant_types' => ['authorization_code', 'refresh_token'],
        'response_types' => ['code'],
        'token_endpoint_auth_method' => 'none',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['client_id', 'redirect_uris', 'grant_types']);
});

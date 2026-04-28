<?php

declare(strict_types=1);

use App\Mcp\Servers\RelaticleServer;
use App\Mcp\Tools\SearchTool;
use App\Models\Company;
use App\Models\People;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

mutates(SearchTool::class);

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
    $this->actingAs($this->user);
});

it('searches across companies and people and returns canonical urls', function (): void {
    Company::factory()->recycle([$this->user, $this->team])->create(['name' => 'Acme Corp']);
    People::factory()->recycle([$this->user, $this->team])->create(['name' => 'Acme Contact']);

    $base = rtrim((string) config('app.url'), '/');

    RelaticleServer::actingAs($this->user)
        ->tool(SearchTool::class, ['query' => 'Acme', 'limit' => 5])
        ->assertOk()
        ->assertStructuredContent(function (AssertableJson $json) use ($base): void {
            $json->has('results', 2)
                ->has('results.0', fn (AssertableJson $row) => $row
                    ->where('url', fn (string $url) => str_starts_with($url, $base))
                    ->has('title')
                    ->has('snippet')
                    ->has('type')
                    ->etc()
                )
                ->has('count')
                ->etc();
        });
});

it('returns empty results for no matches', function (): void {
    RelaticleServer::actingAs($this->user)
        ->tool(SearchTool::class, ['query' => 'ZZZnonexistent999'])
        ->assertOk()
        ->assertStructuredContent(['results' => [], 'count' => 0]);
});

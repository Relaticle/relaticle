<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Enums\Lab;
use Relaticle\Chat\Agents\CrmAssistant;

it('passes disable_parallel_tool_use to Anthropic via tool_choice', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    Http::fake([
        'https://api.anthropic.com/*' => Http::response([
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [['type' => 'text', 'text' => 'Hello!']],
            'model' => 'claude-sonnet-4-5',
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 5, 'output_tokens' => 3],
        ]),
    ]);

    DB::table('agent_conversations')->insert([
        'id' => '019df800-0000-7000-8000-000000000001',
        'user_id' => (string) $user->getKey(),
        'team_id' => $user->currentTeam->getKey(),
        'title' => '',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $agent = resolve(CrmAssistant::class);
    $agent->withConversationId('019df800-0000-7000-8000-000000000001');

    $agent->prompt('hi', provider: 'anthropic', model: 'claude-sonnet-4-5');

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return ($body['tool_choice']['disable_parallel_tool_use'] ?? false) === true;
    });
});

it('returns no tool_choice override for non-anthropic providers', function (): void {
    $agent = resolve(CrmAssistant::class);

    expect($agent->providerOptions('openai'))->toBe([]);
    expect($agent->providerOptions('gemini'))->toBe([]);
    expect($agent->providerOptions(Lab::Anthropic))->toHaveKey('tool_choice');
});

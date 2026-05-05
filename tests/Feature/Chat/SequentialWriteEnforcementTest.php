<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;
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

    /** @var CrmAssistant $agent */
    $agent = app(CrmAssistant::class);
    $agent->withConversationId('019df800-0000-7000-8000-000000000001');

    try {
        $agent->prompt('hi', provider: 'anthropic', model: 'claude-sonnet-4-5');
    } catch (Throwable) {
        // We only care about the request body; ignore downstream errors.
    }

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return ($body['tool_choice']['disable_parallel_tool_use'] ?? false) === true;
    });
});

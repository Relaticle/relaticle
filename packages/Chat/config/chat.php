<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | AI Credit Limits per Plan
    |--------------------------------------------------------------------------
    */

    'credits' => [
        'free' => (int) env('AI_CREDITS_FREE', 100),
        'starter' => (int) env('AI_CREDITS_STARTER', 500),
        'pro' => (int) env('AI_CREDITS_PRO', 2000),
        'enterprise' => (int) env('AI_CREDITS_ENTERPRISE', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Credit Multipliers
    |--------------------------------------------------------------------------
    */

    'model_multipliers' => [
        'claude-opus-4-5' => 3.0,
        'claude-sonnet-4-5' => 1.0,
        'gpt-4o' => 1.5,
        'gemini-2.5-pro' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Call Credit Bonus
    |--------------------------------------------------------------------------
    */

    'tool_call_credit_bonus' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Pending Action Expiry (minutes)
    |--------------------------------------------------------------------------
    */

    'pending_action_expiry_minutes' => 15,

    /*
    |--------------------------------------------------------------------------
    | Conversation Context Window
    |--------------------------------------------------------------------------
    |
    | Maximum number of past conversation messages (user + assistant + tool
    | results) sent to the model on each request. Lower values reduce token
    | usage; higher values give the model more memory of earlier turns.
    */

    'max_conversation_messages' => (int) env('CHAT_MAX_CONVERSATION_MESSAGES', 100),

];

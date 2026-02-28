<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Relaticle\Workflow\Models\Workflow;
use Relaticle\Workflow\Triggers\WebhookTrigger;

class WebhookTriggerController extends Controller
{
    /**
     * Handle an incoming webhook request and trigger the associated workflow.
     *
     * Accepts any JSON payload, which is passed as context to the workflow
     * execution under the 'webhook' key.
     */
    public function __invoke(Request $request, string $workflowId, WebhookTrigger $trigger): JsonResponse
    {
        $workflow = Workflow::findOrFail($workflowId);

        // Verify HMAC signature if webhook_secret is set
        if ($workflow->webhook_secret) {
            $signature = $request->header('X-Signature', '');
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $workflow->webhook_secret);

            if (! hash_equals($expectedSignature, $signature)) {
                return response()->json(['error' => 'Invalid webhook signature.'], 401);
            }
        }

        try {
            $trigger->trigger($workflow, $request->all());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Webhook processed successfully.']);
    }
}

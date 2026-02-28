<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Relaticle\Workflow\WorkflowManager;
use Symfony\Component\HttpFoundation\Response;

class WorkflowTenancyMiddleware
{
    public function __construct(private readonly WorkflowManager $manager) {}

    /**
     * Handle an incoming request by resolving the current tenant identifier
     * and storing it in the request attributes for downstream use.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenancyConfig = $this->manager->getTenancyConfig();

        if ($tenancyConfig) {
            $tenantId = ($tenancyConfig['resolver'])();
            // Store in request for controllers to use
            $request->attributes->set('workflow_tenant_id', $tenantId);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

use App\Http\Middleware\SetApiTeamContext;
use App\Mcp\Servers\RelaticleServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp', RelaticleServer::class)
    ->middleware(['auth:api', 'throttle:mcp', SetApiTeamContext::class]);

Mcp::local('relaticle', RelaticleServer::class);

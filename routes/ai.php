<?php

declare(strict_types=1);

use App\Http\Middleware\SetApiTeamContext;
use App\Mcp\Servers\RelaticleServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/relaticle', RelaticleServer::class)
    ->middleware(['auth:sanctum', SetApiTeamContext::class]);

<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Filament\Resources\CompanyResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

final readonly class LoginResponse implements \Filament\Auth\Http\Responses\Contracts\LoginResponse
{
    /** @phpstan-ignore return.unusedType */
    public function toResponse($request): RedirectResponse|Redirector // @pest-ignore-type
    {
        return redirect()->intended(CompanyResource::getUrl('index', ['tenant' => $request->user('web')->currentTeam->getKey()]));
    }
}

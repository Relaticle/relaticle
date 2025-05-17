<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Filament\App\Resources\CompanyResource;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

final readonly class LoginResponse implements Responsable
{
    /** @phpstan-ignore return.unusedType */
    public function toResponse($request): RedirectResponse|Redirector // @pest-ignore-type
    {
        return redirect()->intended(CompanyResource::getUrl('index', ['tenant' => $request->user()->currentTeam->getKey()]));
    }
}

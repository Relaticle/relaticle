<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('has a plan column on teams', function (): void {
    expect(Schema::hasColumn('teams', 'plan'))->toBeTrue();
});

it('defaults new teams to the Free plan value', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    // Cast wired in Task 3 — for now assert the raw default.
    expect($user->currentTeam->getRawOriginal('plan'))->toBe('free');
});

it('does not have a plan column on ai_credit_balances after migration', function (): void {
    expect(Schema::hasColumn('ai_credit_balances', 'plan'))->toBeFalse();
});

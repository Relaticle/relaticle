<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\AddSubscriberTagsJob;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use LogicException;

/** Cannot be final — Sanctum::actingAs() uses Mockery to mock this class in tests */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'abilities',
        'expires_at',
        'team_id',
    ];

    protected static function booted(): void
    {
        self::creating(function (PersonalAccessToken $token): void {
            if ($token->team_id && $token->tokenable instanceof User) {
                abort_unless(
                    $token->tokenable->belongsToTeam(Team::query()->find($token->team_id)),
                    403,
                    'Token team_id must belong to the tokenable user.',
                );
            }
        });

        self::updating(function (PersonalAccessToken $token): void {
            if ($token->isDirty('team_id')) {
                throw_if($token->getOriginal('team_id') !== null, LogicException::class, 'The team_id attribute cannot be changed after it has been set.');

                if ($token->team_id && $token->tokenable instanceof User) {
                    abort_unless(
                        $token->tokenable->belongsToTeam(Team::query()->find($token->team_id)),
                        403,
                        'Token team_id must belong to the tokenable user.',
                    );
                }
            }
        });

        self::created(function (PersonalAccessToken $token): void {
            if (! config('mailcoach-sdk.enabled_subscribers_sync', false)) {
                return;
            }

            $user = $token->tokenable;

            if (! $user instanceof User || ! $user->mailcoach_subscriber_uuid) {
                return;
            }

            $existingTokenCount = PersonalAccessToken::query()
                ->where('tokenable_type', 'user')
                ->where('tokenable_id', $user->id)
                ->count();

            if ($existingTokenCount > 1) {
                return;
            }

            dispatch(new AddSubscriberTagsJob(
                $user->mailcoach_subscriber_uuid,
                [SubscriberTagEnum::HAS_API_TOKEN->value],
            ))->afterCommit();
        });
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriberTagEnum;
use App\Jobs\Email\AddSubscriberTagsJob;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class AiSummary extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasTeam;
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'summarizable_type',
        'summarizable_id',
        'summary',
        'model_used',
        'prompt_tokens',
        'completion_tokens',
    ];

    protected static function booted(): void
    {
        self::created(function (AiSummary $summary): void {
            if (! config('mailcoach-sdk.enabled_subscribers_sync', false)) {
                return;
            }

            /** @var User|null $user */
            $user = auth()->user();

            if (! $user instanceof User || ! $user->mailcoach_subscriber_uuid) {
                return;
            }

            $teamIds = $user->allTeams()->pluck('id');
            $existingCount = AiSummary::query()->whereIn('team_id', $teamIds)->count();

            if ($existingCount > 1) {
                return;
            }

            dispatch(new AddSubscriberTagsJob(
                $user->mailcoach_subscriber_uuid,
                [SubscriberTagEnum::HAS_AI_USAGE->value],
            ))->afterCommit();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function summarizable(): MorphTo
    {
        return $this->morphTo();
    }
}

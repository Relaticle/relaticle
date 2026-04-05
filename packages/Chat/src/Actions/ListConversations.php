<?php

declare(strict_types=1);

namespace Relaticle\Chat\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class ListConversations
{
    /**
     * @return Collection<int, \stdClass>
     */
    public function execute(User $user, int $limit = 50): Collection
    {
        return DB::table('agent_conversations')
            ->where('user_id', $user->getKey())
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'created_at', 'updated_at']);
    }
}

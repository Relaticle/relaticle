<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class TenantFkValidator
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, class-string<Model>>  $fkToModelMap
     */
    public static function assertOwned(User $user, array $data, array $fkToModelMap): void
    {
        $teamId = $user->current_team_id;

        if ($teamId === null) {
            throw ValidationException::withMessages(['team' => 'No active team.']);
        }

        foreach ($fkToModelMap as $field => $modelClass) {
            $value = $data[$field] ?? null;
            if ($value === null) {
                continue;
            }
            if ($value === '') {
                continue;
            }

            $owned = $modelClass::query()
                ->where('team_id', $teamId)
                ->whereKey($value)
                ->exists();

            if (! $owned) {
                throw ValidationException::withMessages([
                    $field => "Referenced {$field} is not in your team.",
                ]);
            }
        }
    }
}

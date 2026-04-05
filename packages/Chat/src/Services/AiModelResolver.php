<?php

declare(strict_types=1);

namespace Relaticle\Chat\Services;

use App\Models\User;
use Relaticle\Chat\Enums\AiModel;

final readonly class AiModelResolver
{
    /**
     * Resolve the provider and model for a chat request.
     *
     * @return array{provider: string|null, model: string|null}
     */
    public function resolve(User $user, ?string $override = null): array
    {
        $aiModel = $this->resolveModel($user, $override);

        return [
            'provider' => $aiModel->provider(),
            'model' => $aiModel->modelId(),
        ];
    }

    private function resolveModel(User $user, ?string $override): AiModel
    {
        if ($override !== null) {
            $model = AiModel::tryFrom($override);

            if ($model !== null && $model !== AiModel::Auto) {
                return $model;
            }
        }

        $preference = $user->ai_preferences['default_model'] ?? 'auto';
        $model = AiModel::tryFrom($preference);

        if ($model !== null && $model !== AiModel::Auto) {
            return $model;
        }

        return AiModel::Auto;
    }
}

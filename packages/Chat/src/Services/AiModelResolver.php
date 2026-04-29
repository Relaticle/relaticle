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
     * When the resolved model is `Auto`, a lightweight heuristic picks
     * Haiku for short, mention-free messages and Sonnet otherwise.
     *
     * @return array{provider: string|null, model: string|null}
     */
    public function resolve(User $user, ?string $override = null, ?string $message = null): array
    {
        $aiModel = $this->resolveModel($user, $override);

        if ($aiModel === AiModel::Auto) {
            $aiModel = $this->autoRoute($message);
        }

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

    private function autoRoute(?string $message): AiModel
    {
        if ($message !== null && strlen($message) < 120 && ! str_contains($message, '@')) {
            return AiModel::ClaudeHaiku;
        }

        return AiModel::ClaudeSonnet;
    }
}

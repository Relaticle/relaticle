<?php

declare(strict_types=1);

namespace App\Filament\Components\Infolists;

use Filament\Infolists\Components\Entry;

final class AvatarName extends Entry
{
    protected string $view = 'filament.components.infolists.avatar-name';

    protected ?string $avatarPath = null;

    protected ?string $namePath = null;

    protected string $avatarSize = 'md';

    protected string $textSize = 'sm';

    protected bool $circular = true;

    public function avatar(string $path): static
    {
        $this->avatarPath = $path;

        return $this;
    }

    public function name(string $path): static
    {
        $this->namePath = $path;

        return $this;
    }

    public function avatarSize(string $size): static
    {
        $this->avatarSize = $size;

        return $this;
    }

    public function textSize(string $size): static
    {
        $this->textSize = $size;

        return $this;
    }

    public function square(): static
    {
        $this->circular = false;

        return $this;
    }

    public function circular(): static
    {
        $this->circular = true;

        return $this;
    }

    public function getState(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof \Illuminate\Database\Eloquent\Model) {
            return [
                'avatar' => null,
                'name' => null,
                'avatarSize' => $this->avatarSize,
                'textSize' => $this->textSize,
                'circular' => $this->circular,
            ];
        }

        $avatarValue = $this->avatarPath !== null && $this->avatarPath !== '' && $this->avatarPath !== '0' ? $this->resolvePath($record, $this->avatarPath) : null;
        $nameValue = $this->namePath !== null && $this->namePath !== '' && $this->namePath !== '0' ? $this->resolvePath($record, $this->namePath) : null;

        return [
            'avatar' => $avatarValue,
            'name' => $nameValue,
            'avatarSize' => $this->avatarSize,
            'textSize' => $this->textSize,
            'circular' => $this->circular,
        ];
    }

    protected function resolvePath(mixed $record, string $path): mixed
    {
        return $this->evaluate(function () use ($record, $path) {
            $segments = explode('.', $path);
            $value = $record;

            foreach ($segments as $segment) {
                if ($value === null) {
                    break;
                }

                $value = $value->{$segment} ?? null;
            }

            return $value;
        });
    }
}

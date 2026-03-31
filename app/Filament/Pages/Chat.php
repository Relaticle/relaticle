<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

final class Chat extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Chat';

    protected static ?string $title = 'AI Chat';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.chat';

    public ?string $conversationId = null;

    public function mount(): void
    {
        /** @var string|null $conversation */
        $conversation = request()->query('conversation');

        $this->conversationId = $conversation;
    }
}

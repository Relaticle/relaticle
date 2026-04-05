<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Relaticle\Chat\Actions\ListConversations;

final class Dashboard extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?string $title = 'Home';

    protected static ?int $navigationSort = -2;

    protected ?string $heading = '';

    protected string $view = 'chat::filament.pages.dashboard';

    public ?string $recentChatTitle = null;

    public ?string $recentChatId = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        $recentChat = (new ListConversations)->execute($user, 1)->first();

        if ($recentChat) {
            $this->recentChatId = $recentChat->id;
            $this->recentChatTitle = $recentChat->title;
        }
    }

    public function getGreeting(): string
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $firstName = explode(' ', $user->name)[0];

        $hour = (int) now()->format('H');

        return match (true) {
            $hour < 12 => "Good morning, {$firstName}.",
            $hour < 18 => "Good afternoon, {$firstName}.",
            default => "Good evening, {$firstName}.",
        };
    }

    /**
     * @return array<int, array{label: string, prompt: string}>
     */
    public function getSuggestedPrompts(): array
    {
        return [
            ['label' => 'CRM overview', 'prompt' => 'Give me a summary of my CRM data'],
            ['label' => 'Overdue tasks', 'prompt' => 'Show my overdue tasks'],
            ['label' => 'Recent companies', 'prompt' => 'List companies added this week'],
            ['label' => 'Pipeline summary', 'prompt' => 'Show my opportunity pipeline summary'],
        ];
    }
}

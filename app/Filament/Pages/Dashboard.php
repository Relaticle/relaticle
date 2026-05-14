<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Date;
use Relaticle\Chat\Actions\ListConversations;

final class Dashboard extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('filament/navigation.items.dashboard');
    }

    public function getTitle(): string
    {
        return __('filament/navigation.items.dashboard');
    }

    protected static ?int $navigationSort = -2;

    protected ?string $heading = '';

    protected string $view = 'chat::filament.pages.dashboard';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

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

        /** @var string $timezone */
        $timezone = $user->timezone ?? config('app.timezone');
        $hour = Date::now($timezone)->hour;

        return match (true) {
            $hour < 12 => "Good morning, {$firstName}.",
            $hour < 18 => "Good afternoon, {$firstName}.",
            default => "Good evening, {$firstName}.",
        };
    }
}

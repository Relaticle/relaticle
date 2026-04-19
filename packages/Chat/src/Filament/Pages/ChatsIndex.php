<?php

declare(strict_types=1);

namespace Relaticle\Chat\Filament\Pages;

use App\Filament\Pages\ChatConversation;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Chat\Actions\DeleteConversation as DeleteConversationAction;
use Relaticle\Chat\Models\AgentConversation;
use Relaticle\Chat\Support\TitleSanitizer;

final class ChatsIndex extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chats';

    protected static ?int $navigationSort = 50;

    protected string $view = 'chat::filament.pages.chats-index';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                /** @var User $user */
                $user = Filament::auth()->user();

                return AgentConversation::query()
                    ->where('user_id', $user->getKey())
                    ->where('team_id', $user->current_team_id)
                    ->latest('updated_at');
            })
            ->columns([
                TextColumn::make('title')
                    ->label('Conversation')
                    ->formatStateUsing(fn (?string $state): string => TitleSanitizer::clean($state ?? 'Untitled'))
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->recordUrl(fn (AgentConversation $record): string => ChatConversation::getUrl(['conversationId' => $record->id]))
            ->recordActions([
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete chat')
                    ->modalDescription('Messages and any pending actions in this chat will be removed. This cannot be undone.')
                    ->action(function (AgentConversation $record): void {
                        /** @var User $user */
                        $user = Filament::auth()->user();

                        (new DeleteConversationAction)->execute($user, $record->id);

                        $this->dispatch('chat:conversation-deleted');
                    }),
            ])
            ->emptyStateHeading('Start your first chat')
            ->emptyStateDescription('Ask questions about your CRM, get summaries, or draft follow-ups.')
            ->emptyStateIcon(Heroicon::OutlinedSparkles)
            ->paginated([25, 50]);
    }
}

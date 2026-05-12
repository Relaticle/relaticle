<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Enums\RiskBand;
use App\Filament\Actions\GenerateRecordSummaryAction;
use App\Filament\Components\Infolists\AvatarName;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\PeopleRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\TasksRelationManager;
use App\Models\Company;
use App\Support\CountryFlag;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Js;
use Relaticle\CustomFields\Facades\CustomFields;

final class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateRecordSummaryAction::make(),
            EditAction::make()->icon('heroicon-o-pencil-square')->label('Edit'),
            ActionGroup::make([
                ActionGroup::make([
                    Action::make('copyPageUrl')
                        ->label('Copy page URL')
                        ->icon('heroicon-o-clipboard-document')
                        ->action(function (Company $record): void {
                            $jsUrl = Js::from(CompanyResource::getUrl('view', [$record]));
                            $this->js("
                            navigator.clipboard.writeText({$jsUrl}).then(() => {
                                new FilamentNotification()
                                    .title('URL copied to clipboard')
                                    .success()
                                    .send()
                            })
                        ");
                        }),
                    Action::make('copyRecordId')
                        ->label('Copy record ID')
                        ->icon('heroicon-o-clipboard-document')
                        ->action(function (Company $record): void {
                            $jsId = Js::from((string) $record->getKey());
                            $this->js("
                            navigator.clipboard.writeText({$jsId}).then(() => {
                                new FilamentNotification()
                                    .title('Record ID copied to clipboard')
                                    .success()
                                    .send()
                            })
                        ");
                        }),
                ])->dropdown(false),
                DeleteAction::make(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Flex::make([
                    Section::make([
                        Flex::make([
                            AvatarName::make('logo')
                                ->avatar('logo')
                                ->name('name')
                                ->avatarSize('lg')
                                ->textSize('xl')
                                ->square()
                                ->label(''),
                            AvatarName::make('creator')
                                ->avatar('creator.avatar')
                                ->name('creator.name')
                                ->avatarSize('sm')
                                ->textSize('sm')  // Default text size for creator
                                ->circular()
                                ->label('Created By'),
                            AvatarName::make('accountOwner')
                                ->avatar('accountOwner.avatar')
                                ->name('accountOwner.name')
                                ->avatarSize('sm')
                                ->textSize('sm')  // Default text size for account owner
                                ->circular()
                                ->label('Account Owner'),
                        ]),
                        CustomFields::infolist()->forSchema($schema)->build(),
                    ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->label('Created Date')
                            ->icon('heroicon-o-clock')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->icon('heroicon-o-clock')
                            ->dateTime(),
                        Section::make('Portfolio Metadata')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                TextEntry::make('partner_source')
                                    ->label('Partner Source')
                                    ->badge()
                                    ->placeholder('—'),
                                TextEntry::make('geography')
                                    ->label('Geography')
                                    ->formatStateUsing(fn (?string $state): string => $state !== null
                                        ? CountryFlag::emoji($state).' '.CountryFlag::name($state)
                                        : '—')
                                    ->placeholder('—'),
                                TextEntry::make('concentration_percentage')
                                    ->label('Concentration')
                                    ->formatStateUsing(fn (?string $state): string => $state !== null
                                        ? number_format((float) $state, 1).'%'
                                        : '—')
                                    ->badge()
                                    ->color(fn (Company $record): string => match ($record->portfolio->riskBand()) {
                                        RiskBand::Low => 'success',
                                        RiskBand::Medium => 'warning',
                                        RiskBand::High => 'danger',
                                    })
                                    ->placeholder('—'),
                                IconEntry::make('is_recurring')
                                    ->label('Recurring Revenue')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-arrow-path')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),
                    ])->grow(false),
                ])->columnSpan('full'),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            PeopleRelationManager::class,
            TasksRelationManager::class,
            NotesRelationManager::class,
        ];
    }
}

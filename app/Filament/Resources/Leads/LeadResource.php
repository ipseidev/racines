<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use App\Models\User;
use App\Support\Percent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Les contacts : les adresses laissées contre un code de réduction (T-141).
 *
 * Une liste, et rien d'autre : pas de fiche, pas d'édition. Le support y
 * répond à deux questions, « ce code existe-t-il ? » et « a-t-il servi ? »,
 * et le fondateur y lit combien de codes se transforment en commandes.
 *
 * L'adresse est chiffrée en base : la recherche porte sur le code, pas sur
 * l'adresse, qu'aucune requête SQL ne sait lire.
 */
final class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.leads.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.leads.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.leads.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label(__('admin.leads.email')),
                TextColumn::make('discount_code')
                    ->label(__('admin.leads.code'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('discount_percent')
                    ->label(__('admin.leads.discount'))
                    ->formatStateUsing(fn (mixed $state): string => Percent::format((int) $state)),
                IconColumn::make('news')
                    ->label(__('admin.leads.news'))
                    ->state(fn (Lead $record): bool => $record->news_opted_in_at !== null)
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.leads.claimed_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('code_used_at')
                    ->label(__('admin.leads.used_at'))
                    ->since()
                    ->placeholder(__('admin.leads.not_used'))
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('code_used_at')
                    ->label(__('admin.leads.filters.used'))
                    ->nullable()
                    ->trueLabel(__('admin.leads.filters.used_yes'))
                    ->falseLabel(__('admin.leads.filters.used_no')),
                TernaryFilter::make('news_opted_in_at')
                    ->label(__('admin.leads.filters.news'))
                    ->nullable()
                    ->trueLabel(__('admin.leads.filters.news_yes'))
                    ->falseLabel(__('admin.leads.filters.news_no')),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
        ];
    }
}

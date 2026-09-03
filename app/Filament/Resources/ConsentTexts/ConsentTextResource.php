<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsentTexts;

use App\Filament\Resources\ConsentTexts\Pages\ListConsentTexts;
use App\Models\ConsentText;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Les textes de consentement, et leurs versions.
 *
 * On n'en modifie jamais un : on en publie une **nouvelle version**, et
 * l'ancienne reste. C'est ce qui rend opposable ce qui a été accepté avant —
 * réécrire un texte sans versionner rendrait inopposable tout l'historique des
 * accords.
 */
final class ConsentTextResource extends Resource
{
    protected static ?string $model = ConsentText::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): string
    {
        // Une méthode et non une propriété : un défaut de propriété
        // ne peut pas appeler `__()`.
        return __('admin.groups.reference');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.consent_texts.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.consent_texts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.consent_texts.title');
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
            ->defaultSort('effective_from', 'desc')
            ->columns([
                TextColumn::make('kind')
                    ->label(__('admin.consent_texts.kind'))
                    ->badge(),
                TextColumn::make('version')
                    ->label(__('admin.consent_texts.version')),
                TextColumn::make('locale')
                    ->label(__('admin.consent_texts.locale')),
                TextColumn::make('effective_from')
                    ->label(__('admin.consent_texts.effective_from'))
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListConsentTexts::route('/'),
        ];
    }
}

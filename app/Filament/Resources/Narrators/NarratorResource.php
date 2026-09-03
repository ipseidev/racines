<?php

declare(strict_types=1);

namespace App\Filament\Resources\Narrators;

use App\Filament\Resources\Narrators\Pages\ListNarrators;
use App\Models\Narrator;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Les narrateurs : leur canal, leur rythme, leurs accords.
 *
 * Les coordonnées ne s'affichent pas en clair. Cette page reste ouverte sur un
 * écran de bureau, et le carnet d'adresses d'une famille n'a pas à y figurer —
 * le support n'a besoin de savoir *par quel canal* joindre quelqu'un, pas de
 * lire son numéro.
 */
final class NarratorResource extends Resource
{
    protected static ?string $model = Narrator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMicrophone;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.narrators.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.narrators.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.narrators.title');
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
                TextColumn::make('first_name')
                    ->label(__('admin.narrators.first_name'))
                    ->searchable(),
                TextColumn::make('project.owner.name')
                    ->label(__('admin.narrators.family'))
                    ->searchable(),
                TextColumn::make('preferred_channel')
                    ->label(__('admin.narrators.channel'))
                    ->badge(),
                TextColumn::make('opted_in_at')
                    ->label(__('admin.narrators.opted_in_at'))
                    ->dateTime('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('contact_deleted_at')
                    ->label(__('admin.narrators.contact_deleted_at'))
                    ->dateTime('d/m/Y')
                    ->placeholder('—'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListNarrators::route('/'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneOptions;

use App\Filament\Resources\PhoneOptions\Pages\ListPhoneOptions;
use App\Models\PhoneOption;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * L'option « enregistrement par téléphone ».
 *
 * Un squelette de lecture au bloc 11 : l'exploitation — planification des
 * appels, script, accord oral, saisie du récit — arrive au bloc 17, avec les
 * outils qui la rendent opérable. Ce qu'on voit ici suffit à répondre à « où
 * en est mon option » sans promettre ce qui n'existe pas.
 */
final class PhoneOptionResource extends Resource
{
    protected static ?string $model = PhoneOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.phone_options.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.phone_options.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.phone_options.title');
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
                TextColumn::make('project.owner.name')
                    ->label(__('admin.phone_options.family'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.phone_options.status'))
                    ->badge(),
                TextColumn::make('entry')
                    ->label(__('admin.phone_options.entry'))
                    ->badge(),
                TextColumn::make('call_day')
                    ->label(__('admin.phone_options.call_day'))
                    ->placeholder('—'),
                TextColumn::make('call_slot')
                    ->label(__('admin.phone_options.call_slot'))
                    ->placeholder('—'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPhoneOptions::route('/'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutboundMessages;

use App\Filament\Resources\OutboundMessages\Pages\ListOutboundMessages;
use App\Models\OutboundMessage;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Les envois, et ce qu'ils sont devenus.
 *
 * C'est la première chose qu'on regarde quand une famille dit « je n'ai rien
 * reçu ». Le statut de livraison vient du webhook du fournisseur : il sépare
 * « jamais envoyé » de « envoyé et non ouvert », et c'est toute la différence
 * entre un bug de notre côté et un silence qu'il faut respecter.
 *
 * Le contenu n'est pas affiché. Un message contient le lien, et un lien
 * lisible dans un back-office est un lien qu'une fuite rend utilisable.
 */
final class OutboundMessageResource extends Resource
{
    protected static ?string $model = OutboundMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): string
    {
        // Une méthode et non une propriété : un défaut de propriété
        // ne peut pas appeler `__()`.
        return __('admin.groups.registers');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.messages.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.messages.title');
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
                TextColumn::make('template')
                    ->label(__('admin.messages.template')),
                TextColumn::make('channel')
                    ->label(__('admin.messages.channel'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('admin.messages.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.messages.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('delivered_at')
                    ->label(__('admin.messages.delivered_at'))
                    ->dateTime('d/m/Y H:i'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListOutboundMessages::route('/'),
        ];
    }
}

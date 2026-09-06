<?php

declare(strict_types=1);

namespace App\Filament\Resources\Books;

use App\Books\AdvanceBookPrinting;
use App\Books\RequestBookReprint;
use App\Enums\BookFormat;
use App\Enums\BookStatus;
use App\Filament\Resources\Books\Pages\ListBooks;
use App\Models\Book;
use App\Models\User;
use App\Services\Storage\MediaStorage;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Les livres, vus par le support.
 *
 * Le pilote imprime **à la main** (§9 du bloc 13) : le support récupère le
 * bon à tirer, passe la commande chez l'imprimeur, saisit la référence, puis
 * marque imprimé et livré. Cette fiche est l'outil de ce travail-là, pas une
 * console d'administration du livre.
 *
 * Trois choses n'existent pas ici, et leur absence est le sujet. Pas de
 * modification de la sélection des chapitres : ce que la famille a approuvé
 * est ce qui s'imprime, et un support bien intentionné qui « corrige » un
 * ordre trahirait cet accord. Pas d'approbation à sa place, pour la même
 * raison. Pas de suppression : un livre commandé existe sur du papier.
 *
 * Le lien du bon à tirer est **régénéré à chaque affichage**, valable une
 * heure : un lien permanent vers le livre d'une famille dans un back-office
 * est exactement ce que le dossier interdit.
 */
final class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.books.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.books.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.books.title');
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
            ->defaultSort('ordered_at', 'desc')
            ->columns([
                TextColumn::make('project.owner.email')
                    ->label(__('admin.books.family'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.books.status'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof BookStatus
                        ? Options::label($state)
                        : (string) $state)
                    ->badge(),
                TextColumn::make('format')
                    ->label(__('admin.books.format'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof BookFormat
                        ? Options::label($state)
                        : (string) $state),
                TextColumn::make('page_count_estimate')
                    ->label(__('admin.books.pages')),
                TextColumn::make('print_order_ref')
                    ->label(__('admin.books.reference'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('ordered_at')
                    ->label(__('admin.books.ordered_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.books.status'))
                    ->options(fn (): array => collect(BookStatus::cases())
                        ->mapWithKeys(fn (BookStatus $status): array => [
                            $status->value => Options::label($status),
                        ])
                        ->all()),
            ])
            ->recordActions([
                Action::make('proof')
                    ->label(__('admin.books.actions.proof'))
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->visible(fn (Book $record): bool => is_string($record->proof_pdf_path) && $record->proof_pdf_path !== '')
                    // Une URL temporaire d'une heure, régénérée à chaque
                    // affichage : le livre d'une famille ne traîne pas dans
                    // un back-office.
                    ->url(fn (Book $record): string => app(MediaStorage::class)
                        ->temporaryUrl((string) $record->proof_pdf_path, 60))
                    ->openUrlInNewTab(),

                self::step('mark_printed', BookStatus::Printed),
                self::step('mark_delivered', BookStatus::Delivered),

                Action::make('reprint')
                    ->label(__('admin.books.actions.reprint'))
                    ->color('danger')
                    ->visible(fn (Book $record): bool => $record->status === BookStatus::Delivered
                        || $record->status === BookStatus::Printed)
                    ->authorize(fn (): bool => self::canWrite())
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('admin.books.actions.reason'))
                            ->helperText(__('admin.books.actions.reason_help'))
                            ->required()
                            ->minLength(10)
                            ->maxLength(500),
                    ])
                    ->action(fn (Book $record, array $data) => app(RequestBookReprint::class)
                        ->handle($record, (string) ($data['reason'] ?? ''))),
            ]);
    }

    /**
     * Une étape du suivi : un horodatage et un statut, avec confirmation.
     *
     * Confirmée parce qu'elle est **irréversible dans les faits** : marquer
     * livré déclenche l'export proactif du bloc 14, et un clic malheureux
     * enverrait à une famille un courriel annonçant un livre qu'elle n'a pas
     * reçu.
     */
    private static function step(string $name, BookStatus $status): Action
    {
        return Action::make($name)
            ->label(__('admin.books.actions.'.$name))
            ->visible(fn (Book $record): bool => $record->status !== $status
                && $record->status->isLocked())
            ->authorize(fn (): bool => self::canWrite())
            ->requiresConfirmation()
            ->action(fn (Book $record) => app(AdvanceBookPrinting::class)->handle($record, $status));
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListBooks::route('/'),
        ];
    }

    private static function canWrite(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.write');
    }
}

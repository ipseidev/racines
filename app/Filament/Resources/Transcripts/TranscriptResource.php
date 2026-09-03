<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transcripts;

use App\Actions\EditTranscript;
use App\Audit\AuditLog;
use App\Enums\TranscriptKind;
use App\Filament\Resources\Transcripts\Pages\ListTranscripts;
use App\Models\Transcript;
use App\Models\User;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Les transcriptions, et la correction d'un mot.
 *
 * La correction passe par `EditTranscript`, qui crée une **nouvelle version**
 * plutôt que de modifier l'existante. Deux conséquences, et elles sont tout
 * l'intérêt : le **mot à mot n'est jamais touché** — c'est un engagement
 * public, pas une préférence technique — et l'historique des corrections
 * reste lisible.
 *
 * Le mot à mot n'est pas corrigible depuis ici, et il ne le sera jamais. Ce
 * qu'on corrige est la version mise au propre : un nom de village mal
 * entendu, un prénom. Corriger le verbatim serait réécrire ce que quelqu'un a
 * dit.
 */
final class TranscriptResource extends Resource
{
    protected static ?string $model = Transcript::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.transcripts.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.transcripts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.transcripts.title');
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
                TextColumn::make('kind')
                    ->label(__('admin.transcripts.kind'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof TranscriptKind
                        ? Options::label($state)
                        : (string) $state)
                    ->badge(),
                TextColumn::make('story.project.owner.name')
                    ->label(__('admin.transcripts.family'))
                    ->searchable(),
                TextColumn::make('version')
                    ->label(__('admin.transcripts.version')),
                IconColumn::make('is_current')
                    ->label(__('admin.transcripts.current'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.transcripts.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label(__('admin.transcripts.kind'))
                    ->options(fn (): array => collect(TranscriptKind::cases())
                        ->mapWithKeys(fn (TranscriptKind $kind): array => [
                            $kind->value => Options::label($kind),
                        ])
                        ->all()),
            ])
            ->recordActions([
                Action::make('edit_text')
                    ->label(__('admin.transcripts.actions.edit'))
                    // Jamais le mot à mot : corriger le verbatim serait
                    // réécrire ce que quelqu'un a dit.
                    ->visible(fn (Transcript $record): bool => $record->kind !== TranscriptKind::Verbatim
                        && $record->is_current)
                    ->authorize(fn (): bool => self::canEditText())
                    ->modalDescription(__('admin.transcripts.actions.edit_help'))
                    ->schema([
                        Textarea::make('text')
                            ->label(__('admin.transcripts.actions.text'))
                            ->default(fn (Transcript $record): string => $record->text)
                            ->required()
                            ->rows(14),
                    ])
                    ->action(function (Transcript $record, array $data): void {
                        $editor = auth()->user();

                        if (! $editor instanceof User) {
                            return;
                        }

                        $before = $record->text;
                        $edited = app(EditTranscript::class)
                            ->handle($record, (string) $data['text'], $editor);

                        /*
                         * Le journal garde la **taille** du changement, pas
                         * les deux textes : une entrée d'audit ne peut pas
                         * être modifiée après coup, et y recopier le récit
                         * intime de quelqu'un en ferait un second endroit
                         * où il vit, celui-là indélébile.
                         */
                        AuditLog::record('edited Transcript', $edited, [
                            'version' => $edited->version,
                            'characters_before' => mb_strlen($before),
                            'characters_after' => mb_strlen($edited->text),
                        ], $edited->story->project);

                        Notification::make()->success()
                            ->title(__('admin.transcripts.actions.done'))
                            ->body(__('admin.transcripts.actions.done_help'))
                            ->send();
                    }),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTranscripts::route('/'),
        ];
    }

    /**
     * `canEditText` et non `canEdit` : ce dernier existe sur `Resource` et
     * doit rester public. Une méthode privée du même nom fait échouer le
     * chargement de la classe — et la collision se découvre à l'exécution,
     * pas à la compilation.
     */
    private static function canEditText(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('transcripts.edit');
    }
}

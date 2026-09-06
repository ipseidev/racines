<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stories;

use App\Enums\TranscriptKind;
use App\Filament\Resources\Stories\Pages\ListStories;
use App\Filament\Resources\Stories\Pages\ViewStory;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;
use App\States\Story\StoryState;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Les histoires, vues par le support.
 *
 * Ce que cette ressource **n'a pas** est son trait principal : **aucune
 * action « Valider » ni « Partager »**. La validation appartient au narrateur,
 * et à lui seul — ou à un mandataire qu'il a explicitement désigné. Un bouton
 * « Partager » dans le back-office serait la porte par laquelle un support
 * bien intentionné trahirait la promesse du produit, et un test le vérifie.
 *
 * Ni création ni édition libre : une histoire naît d'une question envoyée, et
 * son texte se corrige par `EditTranscript`, qui garde l'historique et ne
 * touche jamais au mot à mot.
 */
final class StoryResource extends Resource
{
    protected static ?string $model = Story::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getNavigationLabel(): string
    {
        return __('admin.stories.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.stories.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.stories.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    /** Jamais : une histoire naît d'une question envoyée. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.stories.identity'))
                ->schema([
                    TextEntry::make('question')
                        ->label(__('admin.stories.question'))
                        ->state(fn (Story $record): string => $record->questionText() ?? '—'),
                    TextEntry::make('state')
                        ->label(__('admin.stories.state'))
                        ->state(fn (Story $record): string => __('initiator.story_state.'.$record->state->getValue())),
                    TextEntry::make('title')
                        ->label(__('admin.stories.story_title'))
                        ->state(fn (Story $record): string => $record->title ?? '—'),
                ])
                ->columns(3),

            /*
             * Le texte, sur la fiche de l'histoire.
             *
             * Il vivait ailleurs — dans une liste globale où l'on cherchait
             * sa ligne parmi celles de toutes les familles. Or le geste du
             * support est « on me signale une faute dans cette histoire-là »,
             * et il n'avait pas de chemin (T-186).
             *
             * Les deux rendus côte à côte, et dans cet ordre : le mot à mot
             * d'abord, parce que c'est lui la référence quand on se demande
             * si une correction est fidèle.
             */
            Section::make(__('admin.stories.text'))
                ->schema([
                    TextEntry::make('verbatim')
                        ->label(__('admin.stories.verbatim'))
                        ->helperText(__('admin.stories.verbatim_help'))
                        ->state(fn (Story $record): string => self::text($record, TranscriptKind::Verbatim)),
                    TextEntry::make('fluide')
                        ->label(__('admin.stories.fluide'))
                        ->state(fn (Story $record): string => self::currentText($record)?->text
                            ?: (string) __('admin.stories.no_text')),
                ])
                ->columns(2),

            Section::make(__('admin.stories.timeline'))
                ->schema([
                    TextEntry::make('recorded_at')
                        ->label(__('admin.stories.recorded_at'))
                        ->state(fn (Story $record): string => self::date($record->recorded_at)),
                    TextEntry::make('validated_at')
                        ->label(__('admin.stories.validated_at'))
                        ->state(fn (Story $record): string => self::date($record->validated_at)),
                    TextEntry::make('shared_at')
                        ->label(__('admin.stories.shared_at'))
                        ->state(fn (Story $record): string => self::date($record->shared_at)),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sequence')
                    ->label(__('admin.stories.sequence'))
                    ->sortable(),
                TextColumn::make('project.owner.name')
                    ->label(__('admin.stories.family'))
                    ->searchable(),
                TextColumn::make('state')
                    ->label(__('admin.stories.state'))
                    // La colonne porte l'objet d'état, pas sa valeur : Spatie
                    // le reconstruit à la lecture, et l'écrire en dur ici
                    // casserait le jour où un état est renommé.
                    ->formatStateUsing(fn (mixed $state): string => __(
                        'initiator.story_state.'.($state instanceof StoryState
                            ? $state->getValue()
                            : (string) $state),
                    ))
                    ->badge(),
                TextColumn::make('recorded_at')
                    ->label(__('admin.stories.recorded_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('admin.stories.state'))
                    ->options(fn (): array => collect(StoryState::all())
                        ->mapWithKeys(fn (string $state): array => [
                            $state => __('initiator.story_state.'.$state),
                        ])
                        ->all()),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListStories::route('/'),
            'view' => ViewStory::route('/{record}'),
        ];
    }

    /**
     * Le texte courant qui se corrige : jamais le mot à mot.
     *
     * Corriger le verbatim serait réécrire ce que quelqu'un a dit. La méthode
     * est publique parce que la fiche s'en sert aussi pour décider si le
     * bouton « Corriger » a lieu d'être.
     */
    public static function currentText(Story $story): ?Transcript
    {
        /*
         * La correction d'abord, la mise au propre ensuite.
         *
         * Les deux restent `is_current` en base : `EditTranscript` dérive la
         * correction du fluide sans éteindre celui-ci, et une requête qui
         * demanderait « le courant qui n'est pas le mot à mot » en trouverait
         * donc deux. Sans ordre explicite, laquelle revient dépend de la page
         * disque — vert en isolation, rouge dans la suite.
         *
         * Trier par version marcherait aujourd'hui, la correction portant
         * toujours un numéro plus grand que le texte dont elle part ; l'ordre
         * de préférence, lui, dit ce qu'on veut et ne dépend d'aucune
         * arithmétique.
         */
        foreach ([TranscriptKind::Edited, TranscriptKind::Fluide] as $kind) {
            $transcript = $story->transcripts()
                ->ofKind($kind)
                ->current()
                ->orderByDesc('version')
                ->first();

            if ($transcript instanceof Transcript) {
                return $transcript;
            }
        }

        return null;
    }

    private static function text(Story $story, TranscriptKind $kind): string
    {
        $text = $story->transcripts()
            ->ofKind($kind)
            ->current()
            ->value('text');

        return is_string($text) && $text !== '' ? $text : __('admin.stories.no_text');
    }

    private static function date(mixed $value): string
    {
        return $value === null ? '—' : $value->translatedFormat('j F Y à H:i');
    }
}

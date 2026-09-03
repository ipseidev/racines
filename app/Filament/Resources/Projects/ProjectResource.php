<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Project;
use App\Models\User;
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
 * Les projets, vus par le support.
 *
 * C'est la porte d'entrée du travail quotidien : on arrive ici depuis un
 * message de famille, et on doit comprendre en un écran où en est le projet
 * — statut, narrateur, prochaine question, et depuis combien de temps c'est
 * silencieux.
 *
 * Aucune création : un projet naît d'une commande payée. Aucune suppression :
 * un projet se termine, s'annule ou se gèle, et chacun de ces états veut dire
 * quelque chose de différent pour la famille.
 */
final class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin.projects.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.projects.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.projects.title');
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.projects.identity'))
                ->schema([
                    TextEntry::make('status')
                        ->label(__('admin.projects.status'))
                        ->state(fn (Project $record): string => __('initiator.status.'.$record->status->value))
                        ->badge(),
                    TextEntry::make('narrator')
                        ->label(__('admin.projects.narrator'))
                        ->state(fn (Project $record): string => $record->primaryNarrator->first_name ?? '—'),
                    TextEntry::make('owner.name')->label(__('admin.projects.initiator')),
                    TextEntry::make('cadence')
                        ->label(__('admin.projects.cadence'))
                        ->state(fn (Project $record): string => __($record->cadence->label())),
                ])
                ->columns(4),

            Section::make(__('admin.projects.rhythm'))
                ->schema([
                    TextEntry::make('next_prompt_at')
                        ->label(__('admin.projects.next_prompt'))
                        ->state(fn (Project $record): string => self::date($record->next_prompt_at)),
                    TextEntry::make('paused_until')
                        ->label(__('admin.projects.paused_until'))
                        ->state(fn (Project $record): string => self::date($record->paused_until)),
                    TextEntry::make('collection_ends_at')
                        ->label(__('admin.projects.collection_ends'))
                        ->state(fn (Project $record): string => self::date($record->collection_ends_at)),
                    TextEntry::make('stories')
                        ->label(__('admin.projects.stories_count'))
                        ->state(fn (Project $record): string => (string) $record->stories()->count()),
                ])
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('owner.name')
                    ->label(__('admin.projects.initiator'))
                    ->searchable(),
                TextColumn::make('primaryNarrator.first_name')
                    ->label(__('admin.projects.narrator'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.projects.status'))
                    ->formatStateUsing(fn (mixed $state): string => __(
                        'initiator.status.'.($state instanceof ProjectStatus ? $state->value : (string) $state),
                    ))
                    ->badge(),
                TextColumn::make('next_prompt_at')
                    ->label(__('admin.projects.next_prompt'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('stories_count')
                    ->label(__('admin.projects.stories_count'))
                    ->counts('stories'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.projects.status'))
                    ->options(fn (): array => collect(ProjectStatus::cases())
                        ->mapWithKeys(fn (ProjectStatus $status): array => [
                            $status->value => __('initiator.status.'.$status->value),
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
            'index' => ListProjects::route('/'),
            'view' => ViewProject::route('/{record}'),
        ];
    }

    private static function date(mixed $value): string
    {
        return $value === null ? '—' : $value->translatedFormat('j F Y à H:i');
    }
}

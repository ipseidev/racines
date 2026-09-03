<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stories\Pages;

use App\Actions\HideStoryAction;
use App\Actions\RestoreStoryAction;
use App\Actions\TrashStoryAction;
use App\Audit\AuditLog;
use App\Filament\Concerns\LogsViews;
use App\Filament\Resources\Stories\StoryResource;
use App\Models\Story;
use App\Models\User;
use App\States\Story\Hidden;
use App\States\Story\Trashed;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * La fiche d'une histoire, côté support.
 *
 * Trois actions, et **aucune** qui décide à la place du narrateur. Masquer et
 * mettre à la corbeille exigent un motif, parce qu'elles ne se font qu'à la
 * demande du narrateur ou de l'Initiateur·rice : le motif est la trace de
 * cette demande, et sans lui l'action serait une décision éditoriale de notre
 * part (règle §9 du bloc 11).
 *
 * Il n'y a ni « Valider » ni « Partager ». C'est délibéré, c'est un critère de
 * sortie du bloc, et un test le vérifie sur le code de tout `app/Filament`.
 */
final class ViewStory extends ViewRecord
{
    use LogsViews;

    protected static string $resource = StoryResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->withReason(
                'hide',
                fn (Story $story): Story => app(HideStoryAction::class)->handle($story),
                fn (Story $story): bool => ! $story->state instanceof Hidden,
            ),
            $this->withReason(
                'trash',
                fn (Story $story): Story => app(TrashStoryAction::class)->handle($story),
                fn (Story $story): bool => ! $story->state instanceof Trashed,
            ),
            Action::make('restore')
                ->label(__('admin.stories.actions.restore'))
                ->visible(fn (Story $record): bool => $record->state instanceof Trashed
                    || $record->state instanceof Hidden)
                ->authorize(fn (): bool => self::canWrite())
                ->requiresConfirmation()
                ->action(function (Story $record): void {
                    app(RestoreStoryAction::class)->handle($record);

                    AuditLog::record('restored Story', $record);

                    Notification::make()->success()
                        ->title(__('admin.stories.actions.done'))
                        ->send();
                }),
        ];
    }

    /**
     * Une action qui exige un motif écrit.
     *
     * @param  callable(Story): Story  $run
     * @param  callable(Story): bool  $visible
     */
    private function withReason(string $name, callable $run, callable $visible): Action
    {
        return Action::make($name)
            ->label(__('admin.stories.actions.'.$name))
            ->visible(fn (Story $record): bool => $visible($record))
            ->authorize(fn (): bool => self::canWrite())
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.stories.actions.reason'))
                    ->helperText(__('admin.stories.actions.reason_help'))
                    ->required()
                    ->minLength(10)
                    ->maxLength(300),
            ])
            ->action(function (Story $record, array $data) use ($name, $run): void {
                $run($record);

                // Le motif part au journal : c'est la trace de la demande du
                // narrateur, et la seule chose qui distingue une action
                // légitime d'une décision éditoriale de notre part.
                AuditLog::record($name.'d Story', $record, [
                    'reason' => $data['reason'] ?? null,
                ]);

                Notification::make()->success()
                    ->title(__('admin.stories.actions.done'))
                    ->send();
            });
    }

    private static function canWrite(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.write');
    }
}

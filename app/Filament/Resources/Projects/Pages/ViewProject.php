<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Actions\RequestPause;
use App\Actions\ResumeFromPause;
use App\Actions\ScheduleNextPrompt;
use App\Audit\AuditLog;
use App\Enums\ProjectStatus;
use App\Filament\Concerns\LogsViews;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * La fiche d'un projet, et les quatre gestes du support.
 *
 * Le plus important est le gel pour décès : il arrête **tout** — questions,
 * relances, notifications, règles du moteur — et c'est le premier geste du
 * playbook, avant même de répondre au message qui nous a prévenus. Geler et
 * attendre ne coûte rien ; continuer à envoyer coûte très cher.
 *
 * Le gel demande un motif, et pas par formalisme : c'est la trace de qui nous
 * a prévenus, et elle sera relue quand la famille demandera ce qui s'est
 * passé.
 */
final class ViewProject extends ViewRecord
{
    use LogsViews;

    protected static string $resource = ProjectResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('pause')
                ->label(__('admin.projects.actions.pause'))
                ->visible(fn (Project $record): bool => ! $record->isPaused()
                    && $record->status !== ProjectStatus::FrozenBereavement)
                ->authorize(fn (): bool => self::canWrite())
                ->schema([
                    TextInput::make('weeks')
                        ->label(__('admin.projects.actions.weeks'))
                        ->helperText(__('admin.projects.actions.weeks_help'))
                        ->numeric()->required()->minValue(1)->maxValue(26),
                ])
                ->action(function (Project $record, array $data): void {
                    $weeks = (int) ($data['weeks'] ?? 2);

                    app(RequestPause::class)->handle($record, now()->addWeeks($weeks));

                    AuditLog::record('paused Project', $record, ['weeks' => $weeks]);

                    self::done();
                }),

            Action::make('resume')
                ->label(__('admin.projects.actions.resume'))
                ->visible(fn (Project $record): bool => $record->isPaused())
                ->authorize(fn (): bool => self::canWrite())
                ->requiresConfirmation()
                ->action(function (Project $record): void {
                    app(ResumeFromPause::class)->resume($record);

                    AuditLog::record('resumed Project', $record);

                    self::done();
                }),

            Action::make('reschedule')
                ->label(__('admin.projects.actions.reschedule'))
                ->visible(fn (Project $record): bool => $record->status === ProjectStatus::Active)
                ->authorize(fn (): bool => self::canWrite())
                ->schema([
                    DateTimePicker::make('at')
                        ->label(__('admin.projects.actions.at'))
                        ->seconds(false)
                        ->required()
                        ->minDate(now()),
                ])
                ->action(function (Project $record, array $data): void {
                    // On passe par l'action du domaine, qui respecte le
                    // créneau et le fuseau du projet : une date posée à la
                    // main enverrait une question à trois heures du matin.
                    $record->next_prompt_at = app(ScheduleNextPrompt::class)
                        ->handle($record, now()->parse((string) $data['at']));
                    $record->save();

                    AuditLog::record('rescheduled Project', $record, [
                        'next_prompt_at' => $record->next_prompt_at?->toIso8601String(),
                    ]);

                    self::done();
                }),

            /*
             * Le gel pour décès. Irréversible depuis cette page, et c'est
             * volontaire : le dégel est une décision de l'administration,
             * après lecture des directives post-mortem et sur demande écrite
             * de la famille (playbook `deces.md`).
             */
            Action::make('freeze')
                ->label(__('admin.projects.actions.freeze'))
                ->color('danger')
                ->icon('heroicon-o-hand-raised')
                ->visible(fn (Project $record): bool => $record->status !== ProjectStatus::FrozenBereavement)
                ->authorize(fn (): bool => self::canWrite())
                ->modalDescription(__('admin.projects.actions.freeze_help'))
                ->schema([
                    Textarea::make('reason')
                        ->label(__('admin.projects.actions.freeze_reason'))
                        ->required()
                        ->minLength(10)
                        ->maxLength(300),
                ])
                ->action(function (Project $record, array $data): void {
                    $record->status = ProjectStatus::FrozenBereavement;
                    // Plus rien ne part : ni question, ni relance. C'est tout
                    // l'objet du gel.
                    $record->next_prompt_at = null;
                    $record->save();

                    AuditLog::record('froze Project', $record, [
                        'reason' => $data['reason'] ?? null,
                    ]);

                    self::done();
                }),
        ];
    }

    private static function done(): void
    {
        Notification::make()->success()->title(__('admin.projects.actions.done'))->send();
    }

    private static function canWrite(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.write');
    }
}

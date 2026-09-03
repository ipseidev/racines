<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OutboundMessageStatus;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketStatus;
use App\Features\PhoneOptionOffer;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Models\Story;
use App\Models\SupportTicket;
use App\Models\User;
use App\States\Story\Shared;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Les cinq nombres du tableau de bord.
 *
 * Un seul widget et non cinq : ce sont cinq compteurs, et Filament les
 * présente côte à côte. Cinq classes pour cinq `count()` n'auraient rien
 * apporté qu'une navigation de plus dans le dépôt.
 *
 * Le choix des cinq est ce qui compte. Deux disent si le produit **tourne**
 * (projets actifs, histoires partagées) ; trois disent s'il faut **agir**
 * maintenant (envois échoués, tickets ouverts, plafond téléphone). Aucun ne
 * dit combien de familles on a — un chiffre de vanité sur un tableau de bord
 * de support est une place volée à un chiffre qui déclenche un geste.
 */
final class PilotOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make(
                __('admin.dashboard.active_projects'),
                (string) Project::query()->where('status', ProjectStatus::Active->value)->count(),
            )->description(__('admin.dashboard.active_projects_help')),

            Stat::make(
                __('admin.dashboard.shared_stories'),
                (string) Story::query()
                    ->where('state', Shared::$name)
                    ->where('shared_at', '>=', now()->subDays(30))
                    ->count(),
            )->description(__('admin.dashboard.shared_stories_help')),

            /*
             * Les envois échoués des dernières vingt-quatre heures. C'est le
             * seul compteur qu'on veut voir à zéro : une question qui n'est
             * pas partie est une semaine perdue pour une famille, et personne
             * ne s'en plaindra — le narrateur ne sait pas qu'il devait
             * recevoir quelque chose.
             */
            Stat::make(
                __('admin.dashboard.failed_messages'),
                (string) OutboundMessage::query()
                    ->where('status', OutboundMessageStatus::Failed->value)
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            )
                ->description(__('admin.dashboard.failed_messages_help'))
                ->color(fn (): string => OutboundMessage::query()
                    ->where('status', OutboundMessageStatus::Failed->value)
                    ->where('created_at', '>=', now()->subDay())
                    ->exists() ? 'danger' : 'success'),

            Stat::make(
                __('admin.dashboard.open_tickets'),
                (string) SupportTicket::query()
                    ->where('status', SupportTicketStatus::Open->value)
                    ->count(),
            )->description(__('admin.dashboard.open_tickets_help')),

            Stat::make(
                __('admin.dashboard.phone_option'),
                PhoneOptionOffer::taken().' / '.PhoneOptionOffer::cap(),
            )->description(__('admin.dashboard.phone_option_help')),
        ];
    }
}

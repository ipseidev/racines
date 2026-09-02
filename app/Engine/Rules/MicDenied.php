<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Actions\OpenSupportTicket;
use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\ClientEventName;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\SupportTicketKind;
use App\Models\ClientEvent;
use App\Models\EngineEvent;
use App\States\Story\Proposed;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Le micro a été refusé.
 *
 * Cette règle ne réagit pas au **temps** mais à un fait : le navigateur a
 * rapporté un refus. La page d'aide par système d'exploitation est déjà
 * affichée côté front, avec le repli par écrit — on ne redemande donc rien au
 * narrateur, et surtout on ne relance pas la demande de micro en boucle.
 *
 * Au **deuxième** refus sur la même histoire, le produit lève la main : un
 * ticket part au support. Une personne de 82 ans qui n'y arrive pas n'écrit
 * pas ; elle abandonne, et personne ne sait pourquoi.
 */
final class MicDenied extends BaseRule
{
    public function __construct(private readonly OpenSupportTicket $tickets) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::MicDenied;
    }

    /**
     * Personne n'est notifié : c'est le support qu'on alerte, et seulement au
     * second refus.
     */
    public function audience(Occurrence $occurrence): EngineAudience
    {
        return EngineAudience::Support;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        return ClientEvent::query()
            ->with(['story.project', 'story.narrator'])
            ->where('event', ClientEventName::MicDenied->value)
            ->whereHas('story', fn ($query) => $query->where('state', Proposed::$name))
            ->get()
            ->groupBy('story_id')
            ->map(function (Collection $events): ?Occurrence {
                $story = $events->first()?->story;

                if ($story === null) {
                    return null;
                }

                return new Occurrence(
                    project: $story->project,
                    story: $story,
                    narrator: $story->narrator,
                    // La tentative est le nombre de refus : c'est ce qui
                    // distingue « ça arrive » de « cette personne est
                    // bloquée ».
                    attempt: min($events->count(), 2),
                );
            })
            ->filter()
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        // Au-delà du deuxième refus, le ticket existe déjà : on n'ouvre pas
        // un dossier par tentative.
        return $occurrence->attempt > 2;
    }

    public function fire(Occurrence $occurrence): array
    {
        if ($occurrence->attempt < 2) {
            // Premier refus : la page d'aide suffit. On consigne, et on se
            // taît — redemander le micro tout de suite ferait fuir.
            return ['help_shown' => true];
        }

        $ticket = $this->tickets->handle(
            $occurrence->project,
            SupportTicketKind::MicDeniedTwice,
            $occurrence->story,
            ['denials' => $occurrence->attempt],
        );

        return ['support_ticket_id' => $ticket->id];
    }

    /**
     * La reprise : le micro a fini par être autorisé.
     */
    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $granted = ClientEvent::query()
            ->where('story_id', $event->story_id)
            ->where('event', ClientEventName::MicGranted->value)
            ->where('created_at', '>=', $event->fired_at)
            ->exists();

        if ($granted) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(7)) ? false : null;
    }
}

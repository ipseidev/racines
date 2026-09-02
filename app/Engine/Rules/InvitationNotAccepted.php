<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Engine\BaseRule;
use App\Engine\InitiatorLoad;
use App\Engine\Occurrence;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Le cadeau est parti, personne ne l'a ouvert.
 *
 * Deux temps, deux destinataires, et l'ordre compte. **J+7 : on parle au
 * narrateur**, doucement — un SMS d'un numéro inconnu se remarque moins qu'on
 * ne croit. **J+14 : on en parle à l'Initiateur·rice**, parce qu'un message
 * d'elle vaut dix des nôtres : sa voix se reconnaît.
 *
 * Puis on s'arrête. Deux relances, pas trois : au-delà, ce n'est plus une
 * invitation, c'est une insistance. Le contact du narrateur est alors marqué
 * pour suppression à J+14+30 — on ne garde pas indéfiniment le téléphone de
 * quelqu'un qui n'a jamais dit oui.
 */
final class InvitationNotAccepted extends BaseRule
{
    public function id(): EngineRuleId
    {
        return EngineRuleId::InvitationNotAccepted;
    }

    /**
     * La première relance va au narrateur, la seconde à l'Initiateur·rice :
     * ce sont deux personnes, et deux quotas.
     */
    public function audience(Occurrence $occurrence): EngineAudience
    {
        return $occurrence->attempt === 1
            ? EngineAudience::Narrator
            : EngineAudience::Initiator;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        [$first, $second] = (array) config('product.engine.invitation_reminder_days');

        return Project::query()
            ->with(['owner', 'primaryNarrator'])
            ->where('status', ProjectStatus::AwaitingAcceptance->value)
            ->whereNotNull('gift_sent_at')
            ->whereNull('accepted_at')
            ->whereNull('refused_at')
            ->where('gift_sent_at', '<=', $now->subDays((int) $first))
            ->get()
            ->map(function (Project $project) use ($now, $second): ?Occurrence {
                $sent = $project->gift_sent_at;

                if ($sent === null) {
                    return null;
                }

                // Au-delà de J+14, la deuxième relance a eu lieu (ou aura
                // lieu) : la tentative reste 2, et la limite la bloquera.
                $attempt = $sent->lte($now->subDays((int) $second)) ? 2 : 1;

                return new Occurrence(
                    project: $project,
                    narrator: $project->primaryNarrator,
                    key: 'invitation',
                    attempt: $attempt,
                );
            })
            ->filter()
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        if ($occurrence->attempt >= 2 && InitiatorLoad::isSaturated($occurrence->project)) {
            return true;
        }

        // Deux relances au total, et pas une de plus.
        return $this->firedFor($occurrence->project) >= 2;
    }

    public function fire(Occurrence $occurrence): array
    {
        return $occurrence->attempt === 1
            ? $this->remindNarrator($occurrence)
            : $this->alertInitiator($occurrence);
    }

    /**
     * @return array<string, mixed>
     */
    private function remindNarrator(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;

        if ($narrator === null) {
            return ['skipped' => 'no_narrator'];
        }

        return $this->tell(
            $narrator,
            $occurrence,
            'invitation_reminder',
            ['inviter' => $occurrence->project->owner->name],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function alertInitiator(Occurrence $occurrence): array
    {
        $project = $occurrence->project;
        $narrator = $occurrence->narrator;

        // Le contact d'une personne qui n'a jamais dit oui ne se garde pas
        // indéfiniment : trente jours après la dernière relance, il part.
        if ($narrator !== null && $narrator->contact_deletion_due_at === null) {
            $narrator->contact_deletion_due_at = now()->addDays(30);
            $narrator->save();
        }

        return [
            ...$this->tell(
                $project->owner,
                $occurrence,
                'invitation_alert',
                ['narrator' => $narrator === null ? '' : $narrator->first_name],
            ),
            'contact_deletion_due_at' => $narrator?->contact_deletion_due_at?->toIso8601String(),
        ];
    }

    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $project = $event->project;

        if ($project->accepted_at !== null) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(30)) ? false : null;
    }

    /**
     * Le lien d'invitation tel qu'il est envoyé. Rangé ici pour que la règle
     * et le bloc 10 partagent la même forme.
     */
    public static function inviteUrl(string $plainToken): string
    {
        return Links::invitation($plainToken);
    }
}

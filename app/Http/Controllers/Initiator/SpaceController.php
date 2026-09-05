<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Enums\EngineAudience;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\States\Story\InBook;
use App\States\Story\Proposed;
use App\States\Story\Shared;
use App\Support\InitiatorProject;
use App\Support\Options;
use App\Support\PhotoPresenter;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Le tableau de bord de l'Initiateur·rice.
 *
 * Elle voit **où en est** chaque histoire, jamais son contenu tant que le
 * narrateur ne l'a pas partagée. C'est le même invariant que pour les
 * proches, et il vaut aussi pour celle qui paie : le narrateur est souverain,
 * y compris face à son enfant qui a offert le service.
 *
 * Le titre apparaît quand l'histoire est partagée — un titre est déjà du
 * contenu, et le montrer plus tôt trahirait la promesse.
 */
final class SpaceController
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::for($user);

        if ($project === null) {
            return inertia('initiator/NoProject');
        }

        $narrator = $project->primaryNarrator;

        return inertia('initiator/Dashboard', [
            'project' => [
                'id' => $project->id,
                'status' => $project->status->value,
                'statusLabel' => __('initiator.status.'.$project->status->value),
                'cadence' => $project->cadence->value,
                'cadenceLabel' => Options::label($project->cadence),
                'promptDay' => $project->prompt_day,
                'promptSlot' => $project->prompt_slot->value,
                'nextPromptAt' => $project->next_prompt_at?->toIso8601String(),
                'pausedUntil' => $project->paused_until?->toIso8601String(),
                'narratorFirstName' => $narrator?->first_name,
            ],
            'stories' => self::timeline($project, $user),
            'hasCurrentStory' => self::hasCurrentStory($project),
            'alerts' => self::alerts($project),
            'listensAsFamilyMember' => self::listensAsFamilyMember($project, $user->email),
            // Les deux liens se **réémettent** à la demande : les jetons sont
            // stockés hachés, un lien en clair n'existe qu'entre son émission
            // et son envoi (invariant du bloc 03).
            'copiedLink' => session('copied_link'),
            'copiedWhatsapp' => session('copied_whatsapp'),
            'copiedSms' => session('copied_sms'),
        ]);
    }

    /**
     * La frise des histoires : un état, et un titre **seulement** si partagée.
     *
     * @return list<array<string, mixed>>
     */
    private static function timeline(Project $project, User $owner): array
    {
        return array_values($project->stories()
            ->with('question')
            ->orderByDesc('sequence')
            ->get()
            ->map(function (Story $story) use ($owner): array {
                $shared = $story->state instanceof Shared || $story->state instanceof InBook;

                return [
                    'id' => $story->id,
                    'sequence' => $story->sequence,
                    'state' => $story->state->getValue(),
                    'label' => __('initiator.story_state.'.$story->state->getValue()),
                    // La question, oui : c'est l'Initiateur·rice qui l'a
                    // choisie. Le titre, seulement si le narrateur a partagé.
                    'question' => $story->questionText(),
                    'title' => $shared ? $story->title : null,
                    'recordedAt' => $story->recorded_at?->toIso8601String(),
                    'sharedAt' => $story->shared_at?->toIso8601String(),
                    /*
                     * Une photo est du **contenu**, comme le texte et la
                     * voix : sur une histoire non partagée, elle ne voit que
                     * ses propres dépôts. Le tableau de bord est « son »
                     * espace, et rien n'y rappellerait qu'une photo jointe
                     * par quelqu'un d'autre ne lui appartient pas encore.
                     */
                    'photos' => PhotoPresenter::forInitiator($story, $owner),
                ];
            })
            ->all());
    }

    /**
     * Y a-t-il une question en cours dont on puisse copier le lien ?
     *
     * On ne rend **pas** le lien ici, et c'est une conséquence directe d'un
     * invariant du bloc 03 : les jetons sont stockés hachés, le lien en clair
     * n'existe qu'entre son émission et son envoi. Il ne peut donc pas être
     * relu — il doit être **réémis**, par un geste explicite (bouton
     * « Copier le lien »), qui révoque le précédent. C'est plus sûr que de
     * garder un lien lisible en base pour la commodité d'un tableau de bord.
     */
    private static function hasCurrentStory(Project $project): bool
    {
        return $project->stories()->where('state', Proposed::$name)->exists();
    }

    /**
     * Les alertes du moteur adressées à l'Initiateur·rice, non encore reprises.
     *
     * @return list<array<string, mixed>>
     */
    private static function alerts(Project $project): array
    {
        return array_values(EngineEvent::query()
            ->where('project_id', $project->id)
            ->whereJsonContains('action_taken->told', EngineAudience::Initiator->value)
            ->whereNull('outcome')
            ->orderByDesc('fired_at')
            ->limit(5)
            ->get()
            ->map(fn (EngineEvent $event): array => [
                'ruleId' => $event->rule_id->value,
                'firedAt' => $event->fired_at->toIso8601String(),
                'message' => __('initiator.alert.'.$event->rule_id->value),
            ])
            ->all());
    }

    /**
     * L'Initiateur·rice écoute comme un proche : a-t-elle sa fiche ?
     *
     * Même raison qu'au-dessus : son lien d'écoute se réémet, il ne se relit
     * pas.
     */
    private static function listensAsFamilyMember(Project $project, string $email): bool
    {
        return $project->familyMembers()->where('email', $email)->exists();
    }
}

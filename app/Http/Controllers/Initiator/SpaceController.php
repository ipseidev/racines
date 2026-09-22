<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Books\ComputeBookReadiness;
use App\Enums\EngineAudience;
use App\Enums\Offer;
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
use App\Support\QuestionQueue;
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
    public function __construct(
        private readonly QuestionQueue $queue,
        private readonly ComputeBookReadiness $readiness,
    ) {}

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
            /*
             * La frise regarde **aussi** devant elle (T-257).
             *
             * La page empilait des histoires passées et s'arrêtait là : rien
             * ne disait ce qui allait arriver, alors que c'est la question
             * qu'on se pose en ouvrant son espace un mardi soir. Les
             * prochaines viennent de `QuestionQueue`, la même file que la page
             * des questions réordonne — et avec leurs dates.
             */
            'upcoming' => array_slice($this->queue->forProject($project), 0, 4),
            /*
             * Où en est l'histoire, **selon R-6**.
             *
             * Mots, minutes de voix, pages estimées, thèmes abordés — jamais
             * un nombre d'histoires : le dossier l'interdit explicitement, et
             * une jauge « 12 sur 65 » dirait que le but est d'épuiser le
             * fonds. Ces quatre mesures existaient déjà, calculées par
             * `ComputeBookReadiness`, mais seulement sur la page « Le livre »,
             * c'est-à-dire là où l'on ne va qu'à la fin.
             */
            'readiness' => $this->readiness->handle($project)->toArray(),
            /*
             * Le décompte, qui est un **fait** et non une progression : ce qui
             * est parti, ce qui est revenu, ce qui est partagé. Aucun
             * dénominateur, donc aucune promesse.
             */
            'counts' => self::counts($project),
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
     * Ce qui s'est passé, compté — et le seul dénominateur vendu.
     *
     * Trois faits : ce qui est parti, ce qui est revenu, ce qui est partagé.
     * Et un dénominateur, un seul : **52 questions** (R-2, v3.0). C'est ce
     * que l'offre vend, ce ne varie pas avec le rythme, et ça se comprend
     * sans calcul — « 12 sur 52 » dit tout.
     *
     * Pas « 12 sur 65 » : le fonds éditorialisé compte soixante-cinq
     * questions et n'est pas un stock à épuiser. Pas une jauge d'histoires
     * vers le livre non plus : R-6 refuse de compter en récits, et c'est la
     * matière — mots, minutes, pages, thèmes — qui décide du livre.
     *
     * La date de fin de collecte accompagne le compte sans le remplacer :
     * elle dépend du rythme et change quand il change, donc elle s'affiche
     * comme une date et jamais comme un dénominateur.
     *
     * Tout est nul tant que la collecte n'a pas commencé : un projet en
     * attente d'acceptation n'a pas de calendrier.
     *
     * @return array{asked: int, recorded: int, shared: int, planned: int|null, endsAt: string|null}
     */
    private static function counts(Project $project): array
    {
        $counts = [
            'asked' => $project->stories()->count(),
            'recorded' => $project->stories()->whereNotNull('recorded_at')->count(),
            'shared' => $project->stories()->whereNotNull('shared_at')->count(),
        ];

        if ($project->collection_started_at === null) {
            return $counts + ['planned' => null, 'endsAt' => null];
        }

        return $counts + [
            // Le pilote est une autre offre, plus courte et annoncée comme
            // telle : il se compte en semaines, pas en cinquante-deux.
            'planned' => $project->offer === Offer::Pilot
                ? null
                : (int) config('product.offer.core_questions'),
            'endsAt' => $project->collection_ends_at?->toIso8601String(),
        ];
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
            // Du plus ancien au plus récent : une frise se lit dans le sens
            // du temps, et celle-ci se prolonge ensuite par ce qui vient.
            ->orderBy('sequence')
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

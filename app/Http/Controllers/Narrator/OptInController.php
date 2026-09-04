<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\AcceptInvitation;
use App\Actions\RecordPostMortemDirectives;
use App\Actions\RefuseInvitation;
use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\PostMortemWish;
use App\Enums\PromptSlot;
use App\Enums\RefusalReason;
use App\Models\ConsentText;
use App\Models\Invitation;
use App\Models\Project;
use App\Services\Storage\MediaStorage;
use App\Support\Options;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Response;

/**
 * La page d'opt-in : le moment H0.
 *
 * Ce qu'elle **ne fait pas** compte autant que ce qu'elle fait. Elle ne
 * propose **aucun** enregistrement avant l'acceptation — pas de micro, pas de
 * question, pas d'aperçu. Quelqu'un qui découvre le service par un cadeau doit
 * pouvoir comprendre de quoi il s'agit sans être déjà en train de faire
 * quelque chose.
 *
 * Les deux boutons sont de **même taille**. Rendre le refus discret ne produit
 * pas des oui, ça produit des gens qui ne répondent pas — et un non franc
 * vaut mieux qu'un silence, pour eux comme pour la mesure.
 */
final readonly class OptInController
{
    public function __construct(
        private AcceptInvitation $accept,
        private RefuseInvitation $refuse,
        private RecordPostMortemDirectives $directives,
    ) {}

    public function show(Request $request, MediaStorage $storage): Response
    {
        $project = self::projectFor($request);
        $narrator = $project->primaryNarrator;

        self::markOpened($project);

        $giftAudio = $project->giftAudio()->first();

        return inertia('narrator/OptIn', [
            'inviterName' => $project->owner->name,
            'firstName' => $narrator?->first_name,
            'personalMessage' => $project->gift_message,
            'giftAudioUrl' => $giftAudio?->derived_mp3_path === null
                ? null
                : $storage->temporaryUrl((string) $giftAudio->derived_mp3_path, 60),
            'phoneMasked' => self::maskPhone($narrator?->phone_e164),
            'phone' => $narrator?->phone_e164,
            'preferredChannel' => $narrator?->preferred_channel->value,
            'addressForm' => $project->address_form->value,
            'cadence' => $project->cadence->value,
            'promptDay' => $project->prompt_day,
            'promptSlot' => $project->prompt_slot->value,
            'consents' => self::consentTexts(),
            'channels' => Options::of(Channel::class),
            'cadences' => Options::of(Cadence::class),
            'slots' => Options::of(PromptSlot::class),
            'addressForms' => Options::of(AddressForm::class),
            'refusalReasons' => Options::of(RefusalReason::class),
            'answered' => $project->accepted_at !== null || $project->refused_at !== null,
            // Les URL d'action viennent du serveur, comme sur la page
            // d'enregistrement : une page qui recompose son chemin à partir de
            // `window.location` casse dès qu'une route est renommée.
            'acceptAction' => route('narrator.optin.accept', ['token' => $request->route('token')]),
            'refuseAction' => route('narrator.optin.refuse', ['token' => $request->route('token')]),
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $project = self::projectFor($request);

        $request->merge(['narrator_phone' => Phone::e164($request->input('narrator_phone'))]);

        $validated = $request->validate([
            // Cinq cases, cinq acceptations. Pas un « j'accepte tout » : le
            // dossier veut les consentements distincts et révocables, et une
            // case unique rendrait la révocation d'un seul impossible.
            'consent_voice_recording' => ['accepted'],
            'consent_transcription' => ['accepted'],
            'consent_ai_rendering' => ['accepted'],
            'consent_family_sharing' => ['accepted'],
            'consent_sensitive_categories' => ['accepted'],
            'preferred_channel' => ['required', new Enum(Channel::class)],
            // Tapé comme on le tape, ramené au format international avant
            // la règle (T-136) : la contrainte est la nôtre, pas la sienne.
            'narrator_phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'cadence' => ['required', new Enum(Cadence::class)],
            'prompt_day' => ['required', 'integer', 'min:1', 'max:7'],
            'prompt_slot' => ['required', new Enum(PromptSlot::class)],
            'address_form' => ['required', new Enum(AddressForm::class)],
        ]);

        $this->accept->handle($project, $validated);

        return redirect()
            ->route('narrator.optin.welcome', ['token' => $request->route('token')])
            ->with('status', __('narrator.optin.accepted'));
    }

    public function refuse(Request $request): RedirectResponse
    {
        $project = self::projectFor($request);

        $validated = $request->validate([
            'reason' => ['nullable', new Enum(RefusalReason::class)],
        ]);

        $reason = isset($validated['reason'])
            ? RefusalReason::from((string) $validated['reason'])
            : null;

        $this->refuse->handle($project, $reason);

        return redirect()->route('narrator.optin.farewell');
    }

    /**
     * L'écran de bienvenue : la fiche contact, et les souhaits pour plus tard.
     *
     * « Plus tard » est toujours proposé, et c'est le bouton par défaut : on
     * ne demande pas à quelqu'un qui vient d'accepter de raconter sa vie de
     * penser d'abord à sa mort.
     */
    public function welcome(Request $request): Response
    {
        $project = self::projectFor($request);

        return inertia('narrator/OptInWelcome', [
            'firstName' => $project->primaryNarrator?->first_name,
            'nextPromptAt' => $project->next_prompt_at?->toIso8601String(),
            'vcardUrl' => route('narrator.vcard'),
            'wishes' => Options::of(PostMortemWish::class),
            'directivesAction' => route('narrator.optin.directives', ['token' => $request->route('token')]),
            'directivesRecorded' => $project->primaryNarrator?->postMortemDirective()->exists() ?? false,
        ]);
    }

    public function farewell(): Response
    {
        return inertia('narrator/OptInFarewell');
    }

    /**
     * Les souhaits pour plus tard, s'il ou elle veut les dire maintenant.
     *
     * Facultatif de bout en bout : la page propose « Plus tard », qui ne poste
     * rien du tout. On ne demande pas à quelqu'un qui vient d'accepter de
     * raconter sa vie de penser d'abord à sa mort.
     */
    public function storeDirectives(Request $request): RedirectResponse
    {
        $project = self::projectFor($request);
        $narrator = $project->primaryNarrator;

        abort_if($narrator === null, 404);

        $validated = $request->validate([
            'wishes' => ['required', new Enum(PostMortemWish::class)],
            'referent_name' => ['nullable', 'string', 'max:120'],
            'referent_contact' => ['nullable', 'string', 'max:180'],
        ]);

        $this->directives->handle(
            $project,
            $narrator,
            PostMortemWish::from((string) $validated['wishes']),
            $validated['referent_name'] ?? null,
            $validated['referent_contact'] ?? null,
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        return redirect()
            ->route('narrator.optin.welcome', ['token' => $request->route('token')])
            ->with('status', __('narrator.optin_welcome.wishes.saved'));
    }

    private static function projectFor(Request $request): Project
    {
        $subject = $request->attributes->get('token_subject');

        abort_unless($subject instanceof Project, 404);

        return $subject->load(['owner', 'primaryNarrator']);
    }

    /**
     * « Vu » sépare « jamais reçu » de « reçu et pas répondu », et c'est toute
     * la différence entre relancer et respecter un silence.
     */
    private static function markOpened(Project $project): void
    {
        $narrator = $project->primaryNarrator;

        if ($narrator === null) {
            return;
        }

        Invitation::query()
            ->where('narrator_id', $narrator->id)
            ->whereNull('opened_at')
            ->latest('sent_at')
            ->first()
            ?->forceFill(['opened_at' => now()])
            ->save();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function consentTexts(): array
    {
        $texts = [];

        foreach (AcceptInvitation::CONSENTS as $kind) {
            $current = ConsentText::current($kind);

            $texts[] = [
                'kind' => $kind->value,
                'label' => __($kind->label()),
                'version' => $current?->version,
                'body' => $current?->body,
            ];
        }

        return $texts;
    }

    private static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        return mb_substr($phone, 0, 4).'•• •• •• '.mb_substr($phone, -2);
    }
}

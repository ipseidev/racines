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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
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
            'email' => $narrator?->email,
            'preferredChannel' => $narrator?->preferred_channel->value,
            'addressForm' => $project->address_form->value,
            'cadence' => $project->cadence->value,
            'promptDay' => $project->prompt_day,
            'promptSlot' => $project->prompt_slot->value,
            'consents' => self::consentTexts(),
            // L'un, l'autre, ou les deux : jamais le téléphone opéré, qui est
            // l'option D-9 et ne se choisit pas depuis une invitation (T-234).
            'channels' => Options::only(Channel::class, Channel::narratorPreferences()),
            'cadences' => Options::of(Cadence::class),
            'slots' => Options::of(PromptSlot::class),
            'addressForms' => Options::of(AddressForm::class),
            'refusalReasons' => Options::of(RefusalReason::class),
            // Les souhaits pour plus tard, repliés sous les accords (T-236). Le
            // choix proposé est celui de la politique sans directive (doc 04
            // §6) : il vient d'ici, jamais d'une constante du front.
            'wishes' => Options::of(PostMortemWish::class),
            'defaultWish' => PostMortemWish::TransferToFamily->value,
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

        $email = trim((string) $request->input('narrator_email', ''));

        $request->merge([
            'narrator_phone' => Phone::e164($request->input('narrator_phone')),
            'narrator_email' => $email === '' ? null : mb_strtolower($email),
        ]);

        $validated = $request->validate([
            // Cinq accords, cinq champs. Pas un « j'accepte tout » : le dossier
            // veut les consentements distincts et révocables, et un champ
            // unique rendrait la révocation d'un seul impossible. La page les
            // envoie tous avec le bouton (T-233) ; le serveur les exige un à un.
            'consent_voice_recording' => ['accepted'],
            'consent_transcription' => ['accepted'],
            'consent_ai_rendering' => ['accepted'],
            'consent_family_sharing' => ['accepted'],
            'consent_sensitive_categories' => ['accepted'],
            'preferred_channel' => ['required', Rule::in(Channel::narratorPreferences())],
            // Tapé comme on le tape, ramené au format international avant
            // la règle (T-136) : la contrainte est la nôtre, pas la sienne.
            'narrator_phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'narrator_email' => ['nullable', 'string', 'email:rfc', 'max:254'],
            'cadence' => ['required', new Enum(Cadence::class)],
            'prompt_day' => ['required', 'integer', 'min:1', 'max:7'],
            'prompt_slot' => ['required', new Enum(PromptSlot::class)],
            'address_form' => ['required', new Enum(AddressForm::class)],
            'wishes' => ['nullable', new Enum(PostMortemWish::class)],
            'referent_name' => ['nullable', 'string', 'max:120'],
            'referent_contact' => ['nullable', 'string', 'max:180'],
        ]);

        self::ensureReachable($project, $validated);

        $project = $this->accept->handle($project, $validated);

        $this->recordWishesIfChosen($project, $validated, $request);

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
     * L'écran de bienvenue : la fiche contact, et un mot sur plus tard.
     *
     * Les souhaits se choisissent sur la page d'acceptation, repliés sous les
     * accords (T-236) ; ici on dit seulement ce qui vaut — le choix fait, ou
     * la politique sans directive —, sans rien redemander à quelqu'un qui
     * vient d'accepter de raconter sa vie.
     */
    public function welcome(Request $request): Response
    {
        $project = self::projectFor($request);

        return inertia('narrator/OptInWelcome', [
            'firstName' => $project->primaryNarrator?->first_name,
            'nextPromptAt' => $project->next_prompt_at?->toIso8601String(),
            'vcardUrl' => route('narrator.vcard'),
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

    /**
     * Les souhaits pour plus tard, s'ils ont été **choisis**.
     *
     * La page propose « transmettre à ma famille » d'avance, replié sous les
     * accords (T-236) : c'est ce qui arrivera de toute façon sans directive,
     * sur demande de la famille (doc 04 §6). Laisser ce choix tel quel n'est
     * pas un acte, et une directive s'accompagne d'un consentement journalisé
     * : on n'écrit donc rien tant que la personne n'a pas choisi autre chose
     * ou désigné quelqu'un. Un journal qui n'enregistrerait que des cases
     * remplies d'avance ne prouverait rien le jour où il faudrait le montrer.
     *
     * @param  array<string, mixed>  $validated
     */
    private function recordWishesIfChosen(Project $project, array $validated, Request $request): void
    {
        $narrator = $project->primaryNarrator;
        $wishes = isset($validated['wishes']) ? PostMortemWish::from((string) $validated['wishes']) : null;
        $referentName = self::given($validated['referent_name'] ?? null);
        $referentContact = self::given($validated['referent_contact'] ?? null);

        $chosen = ($wishes !== null && $wishes !== PostMortemWish::TransferToFamily)
            || $referentName !== null
            || $referentContact !== null;

        if ($narrator === null || ! $chosen) {
            return;
        }

        $this->directives->handle(
            $project,
            $narrator,
            $wishes ?? PostMortemWish::TransferToFamily,
            $referentName,
            $referentContact,
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );
    }

    private static function given(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Le canal choisi doit pouvoir joindre la personne.
     *
     * Choisir « SMS » sans numéro, ou « courriel » sans adresse, c'est accepter
     * un cadeau dont aucune question n'arrivera jamais — et personne ne s'en
     * apercevrait avant la relance. Ce qui n'est pas renvoyé par le formulaire
     * garde sa valeur connue ; ce qui manque au bout du compte est refusé, à
     * l'endroit du champ (T-234).
     *
     * @param  array<string, mixed>  $validated
     */
    private static function ensureReachable(Project $project, array $validated): void
    {
        $narrator = $project->primaryNarrator;
        $channels = Channel::from((string) $validated['preferred_channel'])->resolve();

        $phone = $validated['narrator_phone'] ?? $narrator?->phone_e164;
        $email = $validated['narrator_email'] ?? $narrator?->email;

        $missing = [];

        if (in_array(Channel::Sms, $channels, true) && ($phone === null || $phone === '')) {
            $missing['narrator_phone'] = __('narrator.optin.settings.phone_required');
        }

        if (in_array(Channel::Email, $channels, true) && ($email === null || $email === '')) {
            $missing['narrator_email'] = __('narrator.optin.settings.email_required');
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }
    }

    private static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        return mb_substr($phone, 0, 4).'•• •• •• '.mb_substr($phone, -2);
    }
}

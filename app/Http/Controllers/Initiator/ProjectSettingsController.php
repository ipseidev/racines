<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\AddLexiconEntry;
use App\Actions\RemoveLexiconEntry;
use App\Actions\RequestPause;
use App\Actions\ScheduleNextPrompt;
use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\PromptSlot;
use App\Features\MandateDelegation;
use App\Models\LexiconEntry;
use App\Support\InitiatorProject;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Response;

/**
 * Les réglages du projet : rythme, forme d'adresse, lexique, pause.
 *
 * Le lexique est ici plutôt que côté narrateur pour une raison pratique :
 * c'est la famille qui sait comment s'écrit le nom du village de sa
 * grand-mère, et le narrateur ne devrait pas avoir à épeler ses souvenirs.
 *
 * Changer la cadence recalcule le prochain envoi tout de suite, sinon le
 * réglage paraîtrait sans effet jusqu'à la semaine suivante.
 */
final readonly class ProjectSettingsController
{
    public function __construct(
        private ScheduleNextPrompt $schedule,
        private AddLexiconEntry $addLexicon,
        private RemoveLexiconEntry $removeLexicon,
        private RequestPause $pause,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        return inertia('initiator/Settings', [
            'narratorFirstName' => $project->primaryNarrator?->first_name,
            'project' => [
                'cadence' => $project->cadence->value,
                'promptDay' => $project->prompt_day,
                'promptSlot' => $project->prompt_slot->value,
                'addressForm' => $project->address_form->value,
                'timezone' => $project->timezone,
                'pausedUntil' => $project->paused_until?->toIso8601String(),
                'nextPromptAt' => $project->next_prompt_at?->toIso8601String(),
            ],
            'lexicon' => array_values($project->lexiconEntries()
                ->orderBy('term')
                ->get()
                ->map(fn (LexiconEntry $entry): array => [
                    'id' => $entry->id,
                    'term' => $entry->term,
                    'replacement' => $entry->replacement,
                    'notes' => $entry->notes,
                ])
                ->all()),
            'cadences' => Options::of(Cadence::class),
            'slots' => Options::of(PromptSlot::class),
            'addressForms' => Options::of(AddressForm::class),
            // Le mandat n'apparaît que si le drapeau est ouvert : une
            // fonctionnalité fermée ne s'annonce pas (T-82).
            'mandateOpen' => MandateDelegation::isOpenFor($project),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'cadence' => ['required', new Enum(Cadence::class)],
            'prompt_day' => ['required', 'integer', 'min:1', 'max:7'],
            'prompt_slot' => ['required', new Enum(PromptSlot::class)],
            'address_form' => ['required', new Enum(AddressForm::class)],
        ]);

        $project->fill($validated);
        // Recalculé tout de suite : sinon le réglage paraîtrait sans effet
        // jusqu'à la semaine suivante.
        $project->next_prompt_at = $this->schedule->handle($project);
        $project->save();

        return back()->with('status', __('initiator.settings.saved'));
    }

    public function addLexicon(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'term' => ['required', 'string', 'max:120'],
            'replacement' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $this->addLexicon->handle(
            $project,
            (string) $validated['term'],
            $validated['replacement'] ?? null,
            $user,
        );

        return back()->with('status', __('initiator.settings.lexicon_added'));
    }

    public function removeLexicon(Request $request, string $entry): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);
        $found = $project->lexiconEntries()->whereKey($entry)->first();

        abort_unless($found instanceof LexiconEntry, 404);

        $this->removeLexicon->handle($found);

        return back()->with('status', __('initiator.settings.lexicon_removed'));
    }

    /**
     * Une pause demandée par l'Initiateur·rice.
     *
     * Elle a toujours un terme, et le narrateur en est prévenu : la
     * confirmation part par la règle `pause_requested` du moteur.
     */
    public function pause(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'weeks' => ['required', 'integer', 'min:1', 'max:26'],
        ]);

        $this->pause->handle($project, now()->addWeeks((int) $validated['weeks']));

        return back()->with('status', __('initiator.settings.paused', [
            'weeks' => (string) $validated['weeks'],
        ]));
    }
}

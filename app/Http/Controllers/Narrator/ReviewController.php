<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\ApplyReviewDecision;
use App\Actions\EditTranscript;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\TranscriptKind;
use App\Models\Story;
use App\Models\Transcript;
use App\Services\Storage\MediaStorage;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * La relecture : le narrateur écoute, lit, corrige, puis décide.
 *
 * Elle sert la variante B et le « décider plus tard » de la variante A, sur
 * le **même** jeton `record` — le narrateur n'a pas deux liens à distinguer
 * dans ses SMS. Le lien meurt quand l'histoire est validée, ce qui est le
 * comportement voulu : il n'y a plus rien à décider.
 *
 * La page affiche les deux textes. Le mot à mot n'est pas caché derrière un
 * réglage : c'est la parole de la personne, et elle a le droit de vérifier ce
 * que la machine en a fait.
 */
final readonly class ReviewController
{
    public function __construct(
        private EditTranscript $edits,
        private ApplyReviewDecision $decisions,
    ) {}

    public function show(Request $request, MediaStorage $storage): Response
    {
        $story = self::reviewableStory($request);
        $recording = $story->currentRecording()->first();
        $derived = $recording?->derived_mp3_path;

        return inertia('narrator/Review', [
            'firstName' => $story->narrator->first_name,
            'addressForm' => $story->project->address_form->value,
            'question' => $story->questionText(),
            'title' => $story->title,
            'fluide' => self::textOf($story, TranscriptKind::Fluide),
            'verbatim' => self::textOf($story, TranscriptKind::Verbatim),
            'readable' => Transcript::readableFor($story)?->text,
            // La mention vient du serveur : dire d'où vient le texte est une
            // obligation (bloc 06 §8), pas une décision d'affichage.
            'aiLabel' => __('family.story.ai_label', ['first_name' => $story->narrator->first_name]),
            'audioUrl' => $derived === null ? null : $storage->temporaryUrl($derived, 60),
            'familyMembers' => $story->project->familyMembers()
                ->orderBy('display_name')
                ->get(['id', 'display_name'])
                ->map(fn ($member): array => ['id' => $member->id, 'name' => $member->display_name])
                ->all(),
        ]);
    }

    public function edit(Request $request): RedirectResponse
    {
        $story = self::reviewableStory($request);

        $validated = $request->validate([
            'text' => ['required', 'string', 'min:1', 'max:100000'],
        ]);

        $text = trim((string) $validated['text']);

        if ($text === '') {
            return back()->withErrors(['text' => __('narrator.review.empty')]);
        }

        $base = Transcript::readableFor($story);
        abort_if($base === null, 404);

        $this->edits->handle($base, $text, $story->narrator);

        return back()->with('status', __('narrator.review.saved'));
    }

    public function decide(Request $request): RedirectResponse
    {
        $story = self::reviewableStory($request);

        $validated = $request->validate([
            'decision' => ['required', Rule::enum(ShareDecision::class)],
            'keep_for_book' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', Rule::enum(StoryVisibility::class)],
            'family_member_ids' => ['sometimes', 'array'],
            'family_member_ids.*' => ['string'],
        ]);

        $decision = ShareDecision::from((string) $validated['decision']);

        $this->decisions->handle(
            $story,
            $decision,
            keepForBook: (bool) ($validated['keep_for_book'] ?? false),
            visibility: isset($validated['visibility'])
                ? StoryVisibility::from((string) $validated['visibility'])
                : StoryVisibility::AllFamily,
            familyMemberIds: array_values(array_map('strval', (array) ($validated['family_member_ids'] ?? []))),
        );

        return redirect()
            ->route('narrator.thanks')
            ->with('thanks', __('narrator.review.thanks.'.$decision->value));
    }

    /**
     * Une histoire n'est relisable qu'une fois transcrite : avant, il n'y a
     * pas de texte, et donc rien à relire ni à décider.
     */
    private static function reviewableStory(Request $request): Story
    {
        $story = $request->attributes->get('token_subject');

        abort_unless($story instanceof Story, 404);
        abort_unless($story->state instanceof ToReview || $story->state instanceof Transcribed, 404);

        return $story;
    }

    private static function textOf(Story $story, TranscriptKind $kind): ?string
    {
        return $story->transcripts()->ofKind($kind)->current()->first()?->text;
    }
}

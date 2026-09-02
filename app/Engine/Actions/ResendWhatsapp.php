<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Actions\IssueRecordToken;
use App\Actions\PickNextQuestion;
use App\Actions\ProposeStory;
use App\Enums\TokenIssuedReason;
use App\Models\AccessToken;
use App\Models\Project;
use App\States\Story\Proposed;
use App\Support\Links;

/**
 * « Je renvoie le lien moi-même. »
 *
 * L'action n'envoie rien : elle **donne** à l'Initiateur·rice un lien à
 * coller, et un lien `wa.me` prérempli. C'est délibéré — un SMS venant de
 * notre numéro se remarque moins qu'un message de sa fille, et le dossier en
 * fait le levier principal de l'invitation restée sans réponse.
 */
final readonly class ResendWhatsapp implements OneTapAction
{
    public function __construct(
        private PickNextQuestion $questions,
        private ProposeStory $stories,
        private IssueRecordToken $tokens,
    ) {}

    public static function name(): string
    {
        return 'resend_whatsapp';
    }

    /** @return array<string, mixed> */
    public function preview(AccessToken $token): array
    {
        return [
            'title' => __('initiator.one_tap.resend_whatsapp.title'),
            'body' => __('initiator.one_tap.resend_whatsapp.body'),
            'button' => __('initiator.one_tap.resend_whatsapp.button'),
        ];
    }

    /** @return array<string, mixed> */
    public function execute(AccessToken $token): array
    {
        $project = $token->subject;

        if (! $project instanceof Project) {
            return ['done' => false];
        }

        $story = $project->stories()->where('state', Proposed::$name)
            ->orderByDesc('sequence')
            ->first();

        if ($story === null) {
            $question = $this->questions->handle($project);

            if ($question === null) {
                return ['done' => false, 'message' => __('initiator.one_tap.resend_whatsapp.no_question')];
            }

            $story = $this->stories->handle($project, $question);
        }

        $issued = $this->tokens->handle($story, TokenIssuedReason::ReissueSupport);
        $url = Links::record($issued->plain);

        $text = __('initiator.one_tap.resend_whatsapp.message', [
            'narrator' => $project->primaryNarrator()->first()?->first_name,
            'link' => $url,
        ]);

        return [
            'done' => true,
            'message' => __('initiator.one_tap.resend_whatsapp.done'),
            'link' => $url,
            // Prérempli : la personne n'a plus qu'à choisir le destinataire.
            'whatsapp' => 'https://wa.me/?text='.rawurlencode($text),
            'suggestion' => __('initiator.one_tap.resend_whatsapp.audio_hint'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\IssueRecordToken;
use App\Actions\ReissueFamilyLink;
use App\Enums\TokenIssuedReason;
use App\Models\FamilyMember;
use App\States\Story\Proposed;
use App\Support\InitiatorProject;
use App\Support\Links;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Réémettre un lien pour le transmettre soi-même.
 *
 * Les jetons sont stockés **hachés** : un lien en clair n'existe qu'entre son
 * émission et son envoi (bloc 03). Il ne peut donc pas être relu pour être
 * affiché — il doit être réémis, ce qui révoque le précédent. C'est le prix de
 * l'invariant, et il est bon marché : un lien qu'on peut relire en base est un
 * lien qu'une fuite de base rend utilisable.
 *
 * Le message WhatsApp est prérempli parce que c'est le levier du dossier : un
 * message d'un proche vaut dix des nôtres.
 */
final readonly class CopyLinkController
{
    public function __construct(
        private IssueRecordToken $records,
        private ReissueFamilyLink $listens,
    ) {}

    public function record(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $story = $project->stories()
            ->where('state', Proposed::$name)
            ->orderByDesc('sequence')
            ->first();

        if ($story === null) {
            return back()->with('status', __('initiator.copy_link.no_story'));
        }

        $issued = $this->records->handle($story, TokenIssuedReason::ReissueSupport);
        $url = Links::record($issued->plain);

        $text = __('initiator.copy_link.whatsapp', [
            'narrator' => $project->primaryNarrator->first_name ?? '',
            'link' => $url,
        ]);

        return back()
            ->with('copied_link', $url)
            ->with('copied_whatsapp', 'https://wa.me/?text='.rawurlencode($text))
            ->with('copied_sms', self::smsLink($project->primaryNarrator?->phone_e164, $text))
            ->with('status', __('initiator.copy_link.ready'));
    }

    /**
     * Un lien `sms:` prérempli. La forme `?&body=` est celle que lisent à la
     * fois iOS et Android ; sans numéro, l'application de messages s'ouvre sur
     * le message seul et la personne choisit le destinataire.
     */
    private static function smsLink(?string $phone, string $text): string
    {
        return 'sms:'.($phone ?? '').'?&body='.rawurlencode($text);
    }

    public function listen(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);
        $member = $project->familyMembers()->where('email', $user->email)->first();

        if (! $member instanceof FamilyMember) {
            return back()->with('status', __('initiator.copy_link.no_family_member'));
        }

        $issued = $this->listens->handle($member, TokenIssuedReason::ReissueSupport);

        return redirect()->away(Links::listen($issued->plain));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\InviteFamilyMember;
use App\Actions\ReissueFamilyLink;
use App\Actions\RemoveFamilyMember;
use App\Models\FamilyMember;
use App\Support\InitiatorProject;
use App\Support\Links;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Le cercle d'écoute, géré par l'Initiateur·rice.
 *
 * Un lien par personne, jamais un lien commun (bloc 08) : c'est ce qui permet
 * de retirer un accès à une seule personne, et de savoir qui a écouté. Le
 * retrait est un `removed_at`, pas une suppression — savoir qu'une personne a
 * eu accès fait partie de ce qu'on doit pouvoir répondre.
 */
final readonly class FamilyController
{
    public function __construct(
        private InviteFamilyMember $invites,
        private ReissueFamilyLink $links,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        return inertia('initiator/Family', [
            'members' => array_values($project->familyMembers()
                ->whereNull('removed_at')
                ->orderBy('display_name')
                ->get()
                ->map(fn (FamilyMember $member): array => [
                    'id' => $member->id,
                    'name' => $member->display_name,
                    'relationship' => $member->relationship,
                    // La coordonnée masquée : cette page se laisse ouverte sur
                    // un écran, et un carnet d'adresses n'a pas à s'y afficher.
                    'contact' => self::mask($member),
                    'canContribute' => (bool) $member->can_contribute,
                    'invitedAt' => $member->invited_at?->toIso8601String(),
                    'firstSeenAt' => $member->first_seen_at?->toIso8601String(),
                    'isYou' => $member->email === $user->email,
                ])
                ->all()),
            'copiedLink' => session('copied_link'),
            // Le nouveau lien s'affiche dans la carte de la personne concernée,
            // là où l'on a cliqué, pas en tête de page (T-149).
            'copiedFor' => session('copied_for'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'relationship' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'required_without:phone_e164'],
            'phone_e164' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/', 'required_without:email'],
            'can_contribute' => ['sometimes', 'boolean'],
        ]);

        $this->invites->handle($project, $user, $validated);

        return back()->with('status', __('initiator.family.invited'));
    }

    public function reissue(Request $request, string $member): RedirectResponse
    {
        $found = self::ownMember($request, $member);

        $issued = $this->links->handle($found);

        return back()
            ->with('copied_link', Links::listen($issued->plain))
            ->with('copied_for', $found->id)
            ->with('status', __('initiator.family.link_reissued'));
    }

    public function destroy(Request $request, string $member): RedirectResponse
    {
        $found = self::ownMember($request, $member);

        // Retiré, pas supprimé, et le jeton révoqué dans le même geste :
        // l'action porte les deux, et le back-office l'appelle aussi.
        app(RemoveFamilyMember::class)->handle($found, 'removed_by_initiator');

        return back()->with('status', __('initiator.family.removed'));
    }

    private static function ownMember(Request $request, string $member): FamilyMember
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $project = InitiatorProject::forOrFail($user);
        $found = $project->familyMembers()->whereKey($member)->first();

        abort_unless($found instanceof FamilyMember, 404);

        return $found;
    }

    /**
     * La coordonnée, masquée. Cette page reste ouverte sur un écran.
     */
    private static function mask(FamilyMember $member): ?string
    {
        if (is_string($member->email) && $member->email !== '') {
            [$name, $domain] = array_pad(explode('@', $member->email, 2), 2, '');

            return mb_substr($name, 0, 2).'•••@'.$domain;
        }

        if (is_string($member->phone_e164) && $member->phone_e164 !== '') {
            return mb_substr($member->phone_e164, 0, 4).'•• •• •• '.mb_substr($member->phone_e164, -2);
        }

        return null;
    }
}

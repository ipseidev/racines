<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\RecordConsent;
use App\Actions\RevokeConsent;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Models\Narrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * La déclaration d'avance, arrêtée ou reprise depuis son espace (D-10).
 *
 * Sans ce contrôleur, la déclaration serait un aller simple — et c'est
 * précisément ce qui la rendrait illicite : le dossier ne l'autorise que
 * « révocable d'un geste ». Un accord permanent qu'on ne peut pas retirer
 * n'est pas un accord, c'est un piège.
 *
 * **Les deux sens ne sont pas gardés pareil, et ce n'est pas un oubli.**
 * Arrêter rend les récits suivants plus privés : demander un code pour
 * devenir plus discrète serait la garde à l'envers, et « d'un geste » cesse
 * d'être vrai. Reprendre expose ce qui ne l'était pas : la route vit donc
 * dans le groupe `sensitive`, comme le fait déjà « ne plus masquer ».
 *
 * Ni l'un ni l'autre ne touche aux histoires déjà là. La déclaration décide
 * de ce qui vient, jamais rétroactivement : une histoire partagée se retire
 * par son propre geste, une histoire gardée ne se partage pas dans son dos.
 */
final readonly class SharingDeclarationController
{
    public function __construct(
        private RecordConsent $consents,
        private RevokeConsent $revoke,
    ) {}

    public function stop(Request $request): RedirectResponse
    {
        $narrator = self::narratorFor($request);
        $project = $narrator->project;

        if ($project->declared_sharing_at === null) {
            return back();
        }

        $project->declared_sharing_at = null;
        $project->save();

        $this->revoke->handle(
            $narrator,
            $project,
            ConsentKind::DeclaredSharing,
            ConsentChannel::Web,
            null,
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        Log::info('story.declared_sharing_stopped', ['project_id' => $project->id]);

        return back()->with('status', __('narrator.space.sharing_stopped'));
    }

    public function resume(Request $request): RedirectResponse
    {
        $narrator = self::narratorFor($request);
        $project = $narrator->project;

        if ($project->declared_sharing_at !== null) {
            return back();
        }

        $this->consents->handle(
            $narrator,
            $project,
            ConsentKind::DeclaredSharing,
            ConsentChannel::Web,
            null,
            ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        $project->declared_sharing_at = now();
        $project->save();

        Log::info('story.declared_sharing_resumed', ['project_id' => $project->id]);

        return back()->with('status', __('narrator.space.sharing_resumed'));
    }

    private static function narratorFor(Request $request): Narrator
    {
        $narrator = $request->attributes->get('token_subject');

        abort_unless($narrator instanceof Narrator, 404);

        return $narrator;
    }
}

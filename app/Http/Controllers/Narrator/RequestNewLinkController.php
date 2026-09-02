<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\RequestNewLink;
use App\Services\Tokens\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * « Demander un nouveau lien », depuis la page d'erreur amicale.
 *
 * Cette route ne résout pas son jeton : elle n'est atteinte que parce que le
 * lien est mort. Elle le retrouve donc par empreinte, sans vérifier sa
 * validité, et refuse tout ce qui n'est pas un lien d'enregistrement hors
 * service — un lien encore valable n'a pas besoin d'être remplacé.
 */
final readonly class RequestNewLinkController
{
    public function __construct(
        private TokenService $tokens,
        private RequestNewLink $action,
    ) {}

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $accessToken = $this->tokens->locate($token);

        abort_if($accessToken === null, 404);
        abort_unless($this->action->canRequestFor($accessToken), 404);

        $this->action->handle($accessToken);

        return back()->with('status', __('narrator.link_unavailable.request_sent'));
    }
}

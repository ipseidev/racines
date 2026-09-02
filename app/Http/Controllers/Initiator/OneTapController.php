<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Engine\Actions\OneTapAction;
use App\Engine\Actions\OneTapRegistry;
use App\Models\AccessToken;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Les actions en un tap, en deux temps.
 *
 * `GET` **montre**, `POST` **exécute**. L'ordre n'est pas négociable : un lien
 * reçu par SMS qui agirait à l'ouverture serait déclenché par le simple aperçu
 * d'un client de messagerie. La page de confirmation lit donc le jeton sans le
 * consommer (`resolve.token:action,peek`), et seul le bouton le grille.
 */
final readonly class OneTapController
{
    public function __construct(private OneTapRegistry $registry) {}

    public function show(Request $request): Response
    {
        $token = self::tokenFor($request);
        $action = $this->actionFor($token);

        return inertia('initiator/OneTapConfirm', [
            ...$action->preview($token),
            'action' => OneTapRegistry::nameIn($token),
            'done' => false,
        ]);
    }

    public function store(Request $request): Response
    {
        $token = self::tokenFor($request);
        $action = $this->actionFor($token);

        return inertia('initiator/OneTapConfirm', [
            ...$action->preview($token),
            ...$action->execute($token),
            'action' => OneTapRegistry::nameIn($token),
        ]);
    }

    /**
     * Un périmètre inconnu est un lien qui n'existe pas.
     */
    private function actionFor(AccessToken $token): OneTapAction
    {
        $action = $this->registry->resolve($token);

        abort_if($action === null, 404);

        return $action;
    }

    private static function tokenFor(Request $request): AccessToken
    {
        $token = $request->attributes->get('access_token');

        abort_unless($token instanceof AccessToken, 404);

        return $token;
    }
}

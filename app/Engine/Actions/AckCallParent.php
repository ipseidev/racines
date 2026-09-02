<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Models\AccessToken;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * « J'appelle mon parent moi-même. »
 *
 * Cette action ne fait **rien**, et c'est tout son intérêt. Elle enregistre
 * qu'un humain a pris le relais, ce qui vaut mieux que n'importe quel message
 * automatique — et elle évite que le moteur relance quelqu'un qui a déjà
 * décroché son téléphone.
 */
final class AckCallParent implements OneTapAction
{
    public static function name(): string
    {
        return 'ack_call_parent';
    }

    /** @return array<string, mixed> */
    public function preview(AccessToken $token): array
    {
        return [
            'title' => __('initiator.one_tap.ack_call_parent.title'),
            'body' => __('initiator.one_tap.ack_call_parent.body'),
            'button' => __('initiator.one_tap.ack_call_parent.button'),
        ];
    }

    /** @return array<string, mixed> */
    public function execute(AccessToken $token): array
    {
        $project = $token->subject;

        if ($project instanceof Project) {
            Log::info('engine.call_acknowledged', ['project_id' => $project->id]);
        }

        return [
            'done' => true,
            'message' => __('initiator.one_tap.ack_call_parent.done'),
        ];
    }
}

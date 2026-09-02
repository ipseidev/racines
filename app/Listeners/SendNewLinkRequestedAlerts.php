<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\IssueRecordToken;
use App\Enums\TokenIssuedReason;
use App\Events\NewLinkRequested;
use App\Models\Story;
use App\Notifications\NewLinkRequestedNotification;
use App\Notifications\PromptNotification;
use App\Support\Brand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Un narrateur a demandé un nouveau lien.
 *
 * Trois choses partent d'ici, et dans cet ordre : un lien neuf **au
 * narrateur**, sur le canal qu'il connaît déjà ; une alerte à
 * l'Initiateur·rice, pour qu'elle sache sans avoir à agir ; une alerte au
 * support, pour qu'un lien qui casse deux fois devienne visible.
 *
 * Le nouveau lien part sur le canal habituel du narrateur, jamais en réponse à
 * la requête HTTP : la page d'erreur est publique, et quiconque détient
 * l'ancienne URL ne doit pas pouvoir en obtenir une neuve à l'écran.
 */
final readonly class SendNewLinkRequestedAlerts
{
    public function __construct(private IssueRecordToken $issueToken) {}

    public function handle(NewLinkRequested $event): void
    {
        $story = $event->token->subject;

        if (! $story instanceof Story) {
            return;
        }

        $issued = $this->issueToken->handle($story, TokenIssuedReason::ReissueSupport);

        $story->narrator->notify(new PromptNotification($story, $issued->plain));

        $story->project->owner->notify(
            new NewLinkRequestedNotification($story, forSupport: false),
        );

        Notification::route('mail', Brand::supportEmail())
            ->notify(new NewLinkRequestedNotification($story, forSupport: true));

        Log::info('token.new_link_issued', [
            'story_id' => $story->id,
            'previous_token_id' => $event->token->id,
            'new_token_id' => $issued->token->id,
        ]);
    }
}

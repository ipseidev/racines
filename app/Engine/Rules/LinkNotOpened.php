<?php

declare(strict_types=1);

namespace App\Engine\Rules;

use App\Actions\IssueRecordToken;
use App\Engine\BaseRule;
use App\Engine\Occurrence;
use App\Enums\Channel;
use App\Enums\EngineRuleId;
use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\Story;
use App\States\Story\Proposed;
use App\Support\Links;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * La question est partie, le lien n'a jamais été ouvert.
 *
 * On renvoie **sur l'autre canal**, et c'est tout l'intérêt de la règle : un
 * SMS qui n'a pas été vu ne sera pas plus vu au deuxième envoi. Le courriel
 * atterrit ailleurs, se lit sur un autre écran, à un autre moment. S'il n'y a
 * pas d'autre canal, on renvoie une fois sur le même — puis on se taît.
 *
 * Un lien neuf à chaque fois : celui d'origine a pu expirer, et un narrateur
 * n'a pas à comprendre pourquoi le lien de mardi ne marche plus.
 */
final class LinkNotOpened extends BaseRule
{
    public function __construct(private readonly IssueRecordToken $tokens) {}

    public function id(): EngineRuleId
    {
        return EngineRuleId::LinkNotOpened;
    }

    public function detect(CarbonImmutable $now): Collection
    {
        $days = (int) config('product.engine.link_not_opened_days');

        return Story::query()
            ->with(['project', 'narrator'])
            ->where('state', Proposed::$name)
            ->whereHas('project', fn ($query) => $query->whereNull('paused_until')->orWhere('paused_until', '<=', now()))
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('access_tokens')
                // `subject_id` est un varchar (clé polymorphe) et `stories.id`
                // un uuid : Postgres refuse de les comparer sans transtypage
                // explicite, et l'erreur ne se voit qu'à l'exécution.
                ->whereRaw('access_tokens.subject_id = stories.id::text')
                ->where('access_tokens.subject_type', (new Story)->getMorphClass())
                ->where('access_tokens.type', TokenType::Record->value)
                ->where('access_tokens.use_count', 0)
                ->whereNull('access_tokens.revoked_at')
                ->where('access_tokens.created_at', '<=', $now->subDays($days)))
            ->get()
            ->map(fn (Story $story): Occurrence => new Occurrence(
                project: $story->project,
                story: $story,
                narrator: $story->narrator,
                attempt: 1,
            ))
            ->values();
    }

    public function isCapped(Occurrence $occurrence): bool
    {
        // Un renvoi par question, jamais deux.
        return $this->firedForStory($occurrence->story?->id) >= 1;
    }

    public function fire(Occurrence $occurrence): array
    {
        $narrator = $occurrence->narrator;
        $story = $occurrence->story;

        if ($narrator === null || $story === null) {
            return ['skipped' => 'no_recipient'];
        }

        $issued = $this->tokens->handle($story, TokenIssuedReason::ResendOtherChannel);
        $channel = self::otherChannel($narrator->preferred_channel ?? Channel::Sms, $narrator);

        return $this->tell(
            $narrator,
            $occurrence,
            'link_resend',
            [],
            [['label' => __('notifications.engine.link_resend.button'), 'url' => Links::record($issued->plain)]],
            ['channel' => $channel->value],
            forceChannel: $channel,
        );
    }

    /**
     * L'autre canal, s'il existe. Sinon le même : mieux vaut un second SMS
     * que rien, et la limite d'un renvoi par question borne l'insistance.
     */
    private static function otherChannel(Channel $preferred, object $narrator): Channel
    {
        $hasPhone = ($narrator->phone_e164 ?? null) !== null;
        $hasEmail = ($narrator->email ?? null) !== null;

        return match (true) {
            $preferred === Channel::Sms && $hasEmail => Channel::Email,
            $preferred === Channel::Email && $hasPhone => Channel::Sms,
            $preferred === Channel::Both && $hasEmail => Channel::Email,
            default => $preferred === Channel::Both ? Channel::Sms : $preferred,
        };
    }

    /**
     * La reprise : le lien a été ouvert dans les sept jours.
     */
    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
    {
        $opened = AccessToken::query()
            ->where('subject_id', (string) $event->story_id)
            ->where('type', TokenType::Record->value)
            ->where('use_count', '>', 0)
            ->exists();

        if ($opened) {
            return true;
        }

        return $event->fired_at->lte($now->subDays(7)) ? false : null;
    }
}

<?php

declare(strict_types=1);

use App\Actions\HideStoryAction;
use App\Actions\ReactToStory;
use App\Enums\AnalyticsEvent;
use App\Enums\ReactionType;
use App\Features\ReactionNotificationTiming;
use App\Jobs\NotifyNarratorOfReactions;
use App\Models\FamilyMember;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\ReactionDigestNotification;
use App\Notifications\ReactionReceivedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;

/**
 * Une histoire partagée, un proche, et un régime de notification choisi.
 *
 * @return array{Story, FamilyMember}
 */
function reactedStory(string $timing = ReactionNotificationTiming::IMMEDIATE): array
{
    $project = Project::factory()->create();
    Feature::for($project)->activate(ReactionNotificationTiming::class, $timing);

    $story = Story::factory()->forProject($project)->shared()->create(['title' => 'Les crêpes']);
    $member = FamilyMember::factory()->create([
        'project_id' => $project->id,
        'display_name' => 'Marie',
    ]);

    return [$story, $member];
}

function react(Story $story, FamilyMember $member, ReactionType $type = ReactionType::Thanks, ?string $comment = null): void
{
    app(ReactToStory::class)->handle($story, $member, $type, $comment);
}

beforeEach(function (): void {
    Notification::fake();
    // La file est synchrone en test : sans ce faux, le job différé d'une
    // minute part **pendant** l'appel à `ReactToStory`, et la fenêtre
    // d'agrégation qu'on veut éprouver n'existe plus.
    Queue::fake();
});

describe('immédiate', function (): void {
    it('prévient le narrateur en nommant la personne et son mot', function (): void {
        [$story, $member] = reactedStory();

        react($story, $member, ReactionType::Thanks, 'Merci maman.');
        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertSentTo(
            $story->narrator,
            ReactionReceivedNotification::class,
            function (ReactionReceivedNotification $notification) use ($story): bool {
                $mail = $notification->toMail($story->narrator);
                $body = implode(' ', [...$mail->introLines, (string) $mail->subject]);

                // « Une réaction » ne fait rien ressentir ; « Marie vous dit
                // merci » si.
                return str_contains($body, 'Marie')
                    && str_contains($body, 'Les crêpes')
                    && str_contains($body, 'Merci maman.');
            },
        );
    });

    it('n’envoie jamais de lien au narrateur', function (): void {
        [$story, $member] = reactedStory();

        react($story, $member);
        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertSentTo(
            $story->narrator,
            ReactionReceivedNotification::class,
            function (ReactionReceivedNotification $notification) use ($story): bool {
                $mail = $notification->toMail($story->narrator);

                // On rapporte une bonne nouvelle, on ne donne pas une tâche.
                return $mail->actionUrl === null;
            },
        );
    });

    it('agrège plusieurs réactions en un seul message', function (): void {
        [$story, $member] = reactedStory();
        $second = FamilyMember::factory()->create([
            'project_id' => $story->project_id,
            'display_name' => 'Paul',
        ]);

        react($story, $member, ReactionType::Heart);
        react($story, $second, ReactionType::Thanks, 'Bravo !');

        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertSentTimes(ReactionReceivedNotification::class, 1);
    });

    it('ne dépasse jamais une notification par histoire et par jour', function (): void {
        [$story, $member] = reactedStory();

        react($story, $member, ReactionType::Heart);

        // La trace des envois est la source de vérité : elle sait ce qui est
        // **parti**, pas ce qu'on a voulu envoyer.
        OutboundMessage::factory()->create([
            'project_id' => $story->project_id,
            'template' => 'reaction_received',
            'payload' => ['story_id' => $story->id],
        ]);

        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertNothingSent();
    });

    it('se taît pendant une pause demandée', function (): void {
        [$story, $member] = reactedStory();
        $story->project->forceFill(['paused_until' => now()->addWeeks(4)])->save();

        react($story, $member);
        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertNothingSent();
    });

    it('se taît si l’histoire a été masquée entre-temps', function (): void {
        [$story, $member] = reactedStory();

        react($story, $member);
        app(HideStoryAction::class)->handle($story);

        // Le prévenir à propos de ce qu'il vient de cacher serait le pire
        // moment pour lui écrire.
        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertNothingSent();
    });

    it('mesure la notification avec son régime', function (): void {
        $analytics = fakeAnalytics();
        [$story, $member] = reactedStory();

        react($story, $member);
        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        $captured = $analytics->captured(AnalyticsEvent::NarratorNotified);

        expect($captured)->toHaveCount(1)
            ->and($captured[0]['properties']['timing'])->toBe('immediate')
            ->and($captured[0]['properties']['story_id'])->toBe($story->id);
    });
});

describe('lendemain matin', function (): void {
    it('n’envoie rien sur le coup', function (): void {
        [$story, $member] = reactedStory(ReactionNotificationTiming::NEXT_MORNING);

        react($story, $member, ReactionType::Thanks, 'Merci maman.');
        app()->call([new NotifyNarratorOfReactions($story->id), 'handle']);

        Notification::assertNothingSent();
    });

    it('envoie le résumé le lendemain', function (): void {
        $analytics = fakeAnalytics();
        [$story, $member] = reactedStory(ReactionNotificationTiming::NEXT_MORNING);

        react($story, $member, ReactionType::Thanks, 'Merci maman.');

        $this->travelTo(now()->addDay()->setTime(9, 0));

        $this->artisan('reactions:send-digests')->assertSuccessful();

        Notification::assertSentTo(
            $story->narrator,
            ReactionDigestNotification::class,
            function (ReactionDigestNotification $notification) use ($story): bool {
                $mail = $notification->toMail($story->narrator);
                $body = implode(' ', $mail->introLines);

                return str_contains($body, 'Marie') && str_contains($body, 'Les crêpes');
            },
        );

        expect($analytics->captured(AnalyticsEvent::NarratorNotified)[0]['properties']['timing'])
            ->toBe('next-morning');
    });

    it('n’envoie qu’un résumé par narrateur', function (): void {
        [$story, $member] = reactedStory(ReactionNotificationTiming::NEXT_MORNING);
        $second = Story::factory()->forProject($story->project)->shared()->create(['title' => 'Le potager']);

        react($story, $member, ReactionType::Heart);
        react($second, $member, ReactionType::Thanks);

        $this->travelTo(now()->addDay()->setTime(9, 0));
        $this->artisan('reactions:send-digests')->assertSuccessful();

        // Trois proches qui réagissent le même soir font un message qui les
        // nomme tous les trois, pas trois messages.
        Notification::assertSentTimes(ReactionDigestNotification::class, 1);
    });

    it('ignore les projets en notification immédiate', function (): void {
        [$story, $member] = reactedStory();

        react($story, $member);

        $this->travelTo(now()->addDay()->setTime(9, 0));
        $this->artisan('reactions:send-digests')->assertSuccessful();

        // Sinon on écrirait deux fois pour la même réaction.
        Notification::assertNotSentTo($story->narrator, ReactionDigestNotification::class);
    });

    it('n’envoie rien pour une histoire masquée depuis la veille', function (): void {
        [$story, $member] = reactedStory(ReactionNotificationTiming::NEXT_MORNING);

        react($story, $member);
        app(HideStoryAction::class)->handle($story);

        $this->travelTo(now()->addDay()->setTime(9, 0));
        $this->artisan('reactions:send-digests')->assertSuccessful();

        Notification::assertNothingSent();
    });
});

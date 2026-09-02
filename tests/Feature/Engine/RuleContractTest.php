<?php

declare(strict_types=1);

use App\Engine\Occurrence;
use App\Engine\RuleRegistry;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Models\Project;

/**
 * Les critères de sortie du bloc 09, éprouvés plutôt que relus.
 */
it('implémente les onze règles de l’annexe C, dans l’ordre', function (): void {
    $declared = array_map(
        fn ($rule): string => $rule->id()->value,
        app(RuleRegistry::class)->all(),
    );

    // L'ordre est du comportement : quand deux règles veulent parler au même
    // narrateur, celle qui vient en premier gagne.
    expect($declared)->toBe(array_column(EngineRuleId::cases(), 'value'));
});

it('donne à chaque règle un destinataire explicite', function (): void {
    $project = Project::factory()->create();
    $occurrence = new Occurrence($project, key: 'contrat');

    foreach (app(RuleRegistry::class)->all() as $rule) {
        expect($rule->audience($occurrence))->toBeInstanceOf(EngineAudience::class);
    }
});

it('donne à chaque règle son texte sous « engine. »', function (): void {
    // Une règle sans message est une règle muette : elle se déclencherait
    // sans que personne n'en sache rien.
    $silent = [EngineRuleId::MicDenied->value];

    $keys = [
        'invitation_not_accepted' => ['invitation_reminder', 'invitation_alert'],
        'link_not_opened' => ['link_resend'],
        'recording_abandoned' => ['draft_waiting'],
        'recorded_not_validated' => ['validation_reminder'],
        'validated_not_listened' => ['new_story_nudge'],
        'three_stories_no_reaction' => ['react_suggestion'],
        'narrator_silence_10d' => ['lighter_question'],
        'narrator_silence_21d' => ['initiator_alert'],
        'pause_requested' => ['pause_confirmed', 'resume'],
        'declining_cadence' => ['slower_rhythm_offer'],
    ];

    foreach (EngineRuleId::cases() as $rule) {
        if (in_array($rule->value, $silent, true)) {
            continue;
        }

        expect(array_key_exists($rule->value, $keys))
            ->toBeTrue("La règle {$rule->value} n’a pas de texte déclaré.");

        foreach ($keys[$rule->value] as $key) {
            $subject = __("notifications.engine.{$key}.subject");
            $line = __("notifications.engine.{$key}.line");

            expect($subject)->not->toBe("notifications.engine.{$key}.subject")
                ->and($line)->not->toBe("notifications.engine.{$key}.line");
        }
    }
});

it('borne la priorité de chaque règle à sa place dans l’annexe', function (): void {
    expect(EngineRuleId::InvitationNotAccepted->priority())->toBe(0)
        ->and(EngineRuleId::DecliningCadence->priority())->toBe(10)
        ->and(EngineRuleId::LinkNotOpened->priority())
        ->toBeLessThan(EngineRuleId::NarratorSilence10d->priority());
});

it('n’expose aucune règle qui solliciterait l’Initiateur·rice sans plafond', function (): void {
    $project = Project::factory()->create();
    $occurrence = new Occurrence($project, key: 'contrat');

    $initiatorRules = array_filter(
        app(RuleRegistry::class)->all(),
        fn ($rule): bool => $rule->audience($occurrence) === EngineAudience::Initiator,
    );

    // Chacune consulte `InitiatorLoad` : quatre actions par mois, R-7. Une
    // Initiateur·rice épuisée ne relance plus personne.
    foreach ($initiatorRules as $rule) {
        $source = (string) file_get_contents(
            (new ReflectionClass($rule))->getFileName() ?: '',
        );

        // `toContain` prend des aiguilles variadiques : un message en second
        // argument deviendrait une seconde chaîne à chercher.
        expect(str_contains($source, 'InitiatorLoad'))
            ->toBeTrue("{$rule->id()->value} ne consulte pas le plafond R-7.");
    }

    expect($initiatorRules)->not->toBeEmpty();
});

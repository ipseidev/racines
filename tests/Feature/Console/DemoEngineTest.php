<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\EngineRuleId;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Story;
use Database\Seeders\DemoProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Le préalable du point 1 du bloc 09, qui ne se fait pas à la main.
 *
 * La feuille des vérifications dit « forcer les horodatages » et s'arrête là.
 * Trois signaux sont à armer, sur trois tables différentes, et deux d'entre
 * eux se lisent à l'envers — le silence se prouve par l'**absence** d'une
 * histoire récente, pas par la présence de quoi que ce soit. Recopier cela en
 * `tinker` à trois heures d'écart de la personne qui l'a écrit, c'est la
 * garantie d'un tick qui ne déclenche rien et d'une heure passée à chercher
 * pourquoi.
 *
 * La commande arme, dit ce qu'elle a armé, et se rejoue.
 */
beforeEach(function (): void {
    $this->seed(DemoProjectSeeder::class);
    Notification::fake();
});

it('refuse de tourner en production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:moteur')->assertFailed();
});

it('le dit quand le décor n’est pas semé', function (): void {
    Project::query()->delete();

    $this->artisan('demo:moteur')
        ->expectsOutputToContain('Décor absent')
        ->assertFailed();
});

it('arme les trois signaux que le tick doit voir', function (): void {
    $this->artisan('demo:moteur')->assertSuccessful();
    $this->artisan('engine:tick')->assertSuccessful();

    $fired = EngineEvent::query()
        ->where('dedupe_key', 'not like', '%:suppressed:%')
        ->pluck('rule_id')
        ->map(fn (EngineRuleId $id): string => $id->value)
        ->all();

    expect($fired)
        ->toContain(EngineRuleId::LinkNotOpened->value)
        ->toContain(EngineRuleId::ValidatedNotListened->value)
        ->toContain(EngineRuleId::NarratorSilence21d->value);
});

/**
 * Le point qui compte vraiment du checkpoint : le moteur relance, il ne
 * harcèle pas. Le silence franchit aussi le seuil des dix jours, et cette
 * règle parle au **même** narrateur que le lien non ouvert. Une seule des deux
 * doit sortir, l’autre est consignée comme supprimée pour que le rappel
 * différé ne perde pas son idempotence.
 */
it('ne fait parler qu\u2019une seule règle au narrateur, le même jour', function (): void {
    $this->artisan('demo:moteur')->assertSuccessful();
    $this->artisan('engine:tick')->assertSuccessful();

    $silence10d = EngineEvent::query()
        ->where('rule_id', EngineRuleId::NarratorSilence10d->value)
        ->first();

    expect($silence10d)->not->toBeNull()
        ->and($silence10d->dedupe_key)->toContain(':suppressed:');
});

it('ne relance rien au second tour', function (): void {
    $this->artisan('demo:moteur')->assertSuccessful();
    $this->artisan('engine:tick')->assertSuccessful();

    $first = EngineEvent::query()->where('dedupe_key', 'not like', '%:suppressed:%')->count();

    $this->artisan('engine:tick')->assertSuccessful();

    $second = EngineEvent::query()->where('dedupe_key', 'not like', '%:suppressed:%')->count();

    expect($second)->toBe($first);
});

it('donne au narrateur un second canal, sans quoi le renvoi n’a nulle part où aller', function (): void {
    $narrator = Project::query()->firstOrFail()->primaryNarrator()->firstOrFail();
    expect($narrator->email)->toBeNull();

    $this->artisan('demo:moteur')->assertSuccessful();

    $narrator->refresh();

    expect($narrator->email)->not->toBeNull()
        ->and($narrator->phone_e164)->not->toBeNull()
        ->and($narrator->preferred_channel)->toBe(Channel::Sms);
});

/**
 * Le défaut trouvé en jouant le checkpoint pour de vrai : le planificateur
 * tourne à :07 de chaque heure, dans son propre conteneur, et il avait déjà
 * consommé deux occurrences avant qu'on arme quoi que ce soit.
 *
 * Conséquence : `recorded_not_validated` avait parlé au narrateur le matin,
 * donc `link_not_opened` sortait supprimée, et `validated_not_listened` était
 * déjà dédupliquée. Le tick annonçait « 1 déclenchement, 2 supprimés, 2
 * ignorés » là où le checkpoint en attend trois — et rien n'expliquait
 * pourquoi. Un checkpoint qu'on ne peut pas rejouer n'est pas un checkpoint.
 */
it('efface la trace du jour, sinon un tick du planificateur rend le checkpoint injouable', function (): void {
    // Le planificateur tourne à :07 de chaque heure, dans son propre
    // conteneur, et il passe avant nous sur un décor pas encore armé :
    // `recorded_not_validated` parle alors au narrateur, et
    // `validated_not_listened` consomme son idempotence.
    $this->artisan('engine:tick')->assertSuccessful();

    $avant = EngineEvent::query()->pluck('id')->all();

    $this->artisan('demo:moteur')->assertSuccessful();
    $this->artisan('engine:tick')->assertSuccessful();

    $apres = EngineEvent::query()
        ->whereNotIn('id', $avant)
        ->where('dedupe_key', 'not like', '%:suppressed:%')
        ->pluck('rule_id')
        ->map(fn (EngineRuleId $id): string => $id->value)
        ->all();

    expect($apres)
        ->toContain(EngineRuleId::LinkNotOpened->value)
        ->toContain(EngineRuleId::ValidatedNotListened->value)
        ->toContain(EngineRuleId::NarratorSilence21d->value);
});

it('se rejoue sans empiler les jetons', function (): void {
    $this->artisan('demo:moteur')->assertSuccessful();
    $this->artisan('demo:moteur')->assertSuccessful();

    $proposed = Story::query()->where('state', 'proposed')->firstOrFail();

    $tokens = AccessToken::query()
        ->where('subject_type', (new Story)->getMorphClass())
        ->where('subject_id', $proposed->id)
        ->where('type', TokenType::Record->value)
        ->count();

    expect($tokens)->toBe(1);
});

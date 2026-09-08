<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Exceptions\Domain\ObjectNotStored;
use App\Models\ConsentText;
use App\Services\Storage\MediaStorage;
use App\Support\Database\EnumCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * Ce qu'on vérifie ici n'est pas le verdict — il dépend d'un décor et n'a de
 * sens qu'en production. C'est que la commande **arrive au bout** : elle sera
 * lancée sur un serveur, rarement, souvent dans l'urgence, et une clé de
 * configuration mal orthographiée n'a alors aucune chance d'être vue avant.
 */

it('parcourt toute la chaîne sans se casser', function () {
    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('Encaisser')
        ->expectsOutputToContain('Livrer')
        ->expectsOutputToContain('Joindre les gens')
        ->expectsOutputToContain('Transformer la voix en texte')
        ->expectsOutputToContain('Stockage des voix')
        ->run();
});

it('dit ce que perd le client quand une clé manque, pas ce qui manque', function () {
    config()->set('cashier.secret', '');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('le bouton « Payer » ne mène nulle part')
        ->run();
});

it('écrit puis relit le stockage, et ne laisse rien derrière lui', function () {
    $this->artisan('prod:check', ['--rapide' => true])->run();

    expect(fn () => app(MediaStorage::class)->head('health/prod-check.txt'))
        ->toThrow(ObjectNotStored::class);
});

/*
 * Le trou que `prod:demo` a trouvé et que cette commande ne voyait pas.
 *
 * `FulfillOrder` recueille deux accords de l'acheteur, et le tunnel affiche
 * les deux cases sans condition : un texte manquant fait lever l'exécution de
 * la commande **dans sa transaction**, le webhook répond 500, et Stripe
 * désactive l'endpoint (T-169, T-222). La question de cette commande est « si
 * quelqu'un achète maintenant, est-ce que ça marche ? » — elle répondait oui.
 */
it('voit un texte de consentement manquant, et dit ce que l’acheteur perd', function () {
    ConsentText::query()->where('kind', ConsentKind::EarlyServiceStart->value)->delete();

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('early_service_start')
        ->expectsOutputToContain('un achat peut ne jamais devenir une commande')
        // Le remède est sur sa propre ligne, donc il survit à une largeur de
        // terminal étroite : c'est lui qu'on vient chercher.
        ->expectsOutputToContain('ConsentTextSeeder')
        ->run();
})->uses(RefreshDatabase::class);

it('se tait quand les douze textes sont en vigueur', function () {
    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('en vigueur')
        ->run();
});

/*
 * La divergence qu'aucun test ne peut voir autrement.
 *
 * `EnumCheck::of($enum)` est évalué au moment où la migration tourne : une
 * base créée par `migrate:fresh` — celle de cette suite — obtient toujours
 * l'énumération complète. Une base migrée pas à pas garde la liste d'alors,
 * et c'est celle-là qui est en production. On reproduit donc l'écart à la
 * main, parce que c'est la seule façon de l'éprouver ici (T-222).
 */
it('voit une contrainte restée en arrière de son énumération', function () {
    ConsentText::query()->whereIn('kind', [
        ConsentKind::DeclaredSharing->value,
        ConsentKind::MandateDelegation->value,
        ConsentKind::EarlyServiceStart->value,
        ConsentKind::MarketingEmail->value,
    ])->delete();

    EnumCheck::drop('consent_texts', 'kind');
    EnumCheck::add('consent_texts', 'kind', [
        'voice_recording', 'transcription', 'ai_rendering', 'family_sharing',
        'sensitive_categories', 'phone_call_recording', 'photo_rights', 'post_mortem_directives',
    ]);

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('Contrainte consent_texts.kind')
        // Sur leur propre ligne, donc lisibles sur un terminal étroit.
        ->expectsOutputToContain('early_service_start')
        ->expectsOutputToContain('EnumCheck::drop')
        ->run();
})->uses(RefreshDatabase::class);

it('ne crie pas au loup sur les trois colonnes volontairement étroites', function () {
    /*
     * `narrators.preferred_channel` n'accepte pas `phone_operator`, et
     * `otp_challenges.channel` n'accepte pas `both` : ces contraintes sont
     * justes, et les peindre en rouge apprendrait à ignorer le rouge.
     */
    $this->artisan('prod:check', ['--rapide' => true])
        ->doesntExpectOutputToContain('Contrainte narrators.preferred_channel')
        ->doesntExpectOutputToContain('Contrainte otp_challenges.channel')
        ->doesntExpectOutputToContain('Contrainte outbound_messages.channel')
        ->run();
});

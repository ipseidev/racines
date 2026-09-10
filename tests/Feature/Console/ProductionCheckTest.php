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

/*
 * La vérification qui manquait le 10 septembre 2026.
 *
 * La suite tourne sur `QUEUE_CONNECTION=sync` (`phpunit.xml`) : c'est le monde
 * exact où `->delay()` ne veut rien dire, et c'est pour cette raison qu'un
 * cadeau programmé pour dix heures a pu partir à neuf heures quarante en
 * production sans qu'aucun test le voie (T-239). La sonde le dit maintenant,
 * et ce test se sert de ce que la suite est justement dans ce monde-là.
 */
it('voit une file qui n’attend pas, et dit ce que le cadeau perd', function () {
    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('un cadeau programmé part à l’instant du paiement')
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

/*
 * Le stockage simulé ne prouve rien d'un envoi navigateur, et le dit.
 *
 * `stockage()` écrit et relit avec **nos** identifiants depuis le serveur :
 * ça prouve le compartiment et la clé, et rien du tout de ce qu'un téléphone
 * arrive à faire — l'envoi va en direct du navigateur vers R2 (T-224).
 */
it('annonce que le stockage simulé n’éprouve aucun envoi navigateur', function () {
    config()->set('services.media.driver', 'fake');

    // Une seule attente : les deux moitiés tiennent sur la même ligne, et
    // `expectsOutputToContain` consomme les lignes dans l'ordre.
    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('aucun envoi navigateur n’est éprouvé')
        ->run();
});

/*
 * L'adresse que verra le navigateur, éprouvée pour elle-même.
 *
 * Ni `stockage()` ni `cors()` ne la touchent : tous deux passent par le point
 * de terminaison **serveur**. Or l'enregistrement part par une URL signée sur
 * `R2_PUBLIC_ENDPOINT`, et un `.env` recopié depuis une machine de
 * développement y laisse une IP privée — l'URL est alors parfaitement signée
 * et injoignable depuis le réseau mobile du narrateur (T-226).
 */
it('signale une adresse d’envoi qui n’est pas celle du stockage', function () {
    config()->set('services.media.driver', 's3');
    config()->set('filesystems.disks.r2.endpoint', 'https://compte.eu.r2.cloudflarestorage.com');
    config()->set('filesystems.disks.r2.public_endpoint', 'http://192.168.1.88:9001');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('R2_PUBLIC_ENDPOINT (http://192.168.1.88:9001) diffère')
        ->run();
});

it('se tait quand les deux adresses concordent', function () {
    config()->set('services.media.driver', 's3');
    config()->set('filesystems.disks.r2.endpoint', 'https://compte.eu.r2.cloudflarestorage.com');
    config()->set('filesystems.disks.r2.public_endpoint', 'https://compte.eu.r2.cloudflarestorage.com');

    $this->artisan('prod:check', ['--rapide' => true])
        ->doesntExpectOutputToContain('diffère de R2_ENDPOINT')
        ->run();
});

it('n’éprouve pas le dépôt en mode rapide', function () {
    config()->set('services.media.driver', 's3');

    // `--rapide` n'appelle aucun prestataire : c'est son contrat, et un dépôt
    // réel prendrait quinze secondes de délai d'attente sur une adresse morte.
    $this->artisan('prod:check', ['--rapide' => true])
        ->doesntExpectOutputToContain('Envoi présigné')
        ->run();
});

/*
 * La faute que l'interface de Cloudflare invite.
 *
 * Le tableau de bord affiche en grand « Public R2.dev Bucket URL », et une
 * variable nommée `R2_PUBLIC_ENDPOINT` semble faite pour l'accueillir. Elle
 * ne l'est pas : `r2.dev` est un domaine de lecture publique, qui ne répond à
 * aucune requête signée de l'API S3 — donc à aucun dépôt. Le navigateur
 * n'obtient même pas de statut, WebKit dit « Load failed », et le message
 * générique « diffère de R2_ENDPOINT » n'expliquerait pas pourquoi (T-227).
 */
it('nomme un R2_PUBLIC_ENDPOINT posé sur r2.dev, et dit quoi mettre', function () {
    config()->set('services.media.driver', 's3');
    config()->set('filesystems.disks.r2.endpoint', 'https://compte.eu.r2.cloudflarestorage.com');
    config()->set('filesystems.disks.r2.public_endpoint', 'https://pub-abc123.r2.dev');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('est un domaine r2.dev')
        // Le remède porte le point de terminaison réel, pas un gabarit.
        ->expectsOutputToContain('R2_PUBLIC_ENDPOINT=https://compte.eu.r2.cloudflarestorage.com')
        // Le même réglage sert les liens d'écoute : le dire évite de croire
        // le sujet clos une fois l'envoi réparé.
        ->expectsOutputToContain('liens d’écoute')
        ->run();
});

/*
 * Trois compartiments, et la juridiction où ils vivent.
 *
 * Deux exigences que rien dans le code ne rattrape : la réplique protège de
 * la perte d'un audio confirmé (SLO du doc 04 §11), et la juridiction UE est
 * choisie à la création, **irréversible** sur R2 et non négociable au dossier.
 * Mieux vaut le savoir avant qu'une famille y ait déposé sa voix (T-228).
 */
function disquesR2(string $endpoint, string $media, string $replique, string $sauvegardes): void
{
    config()->set('services.media.driver', 's3');
    config()->set('filesystems.disks.r2.endpoint', $endpoint);
    config()->set('filesystems.disks.r2.public_endpoint', $endpoint);
    config()->set('filesystems.disks.r2.bucket', $media);
    config()->set('filesystems.disks.r2_replica.bucket', $replique);
    config()->set('filesystems.disks.r2_backups.bucket', $sauvegardes);
}

it('refuse qu’une sauvegarde partage le compartiment qu’elle sauvegarde', function () {
    disquesR2('https://compte.eu.r2.cloudflarestorage.com', 'racines', 'racines-replica', 'racines');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('media et sauvegardes partagent le même compartiment')
        ->run();
});

it('signale un point de terminaison sans juridiction UE', function () {
    disquesR2('https://compte.r2.cloudflarestorage.com', 'media', 'media-replica', 'backups');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('ne porte pas « .eu. »')
        // Sans confondre juridiction et localisation : « Western Europe »
        // dit où les données vivent, la juridiction l'impose et la verrouille.
        ->expectsOutputToContain('localisation')
        ->run();
});

it('se tait quand les trois sont distincts et en juridiction UE', function () {
    disquesR2('https://compte.eu.r2.cloudflarestorage.com', 'media', 'media-replica', 'backups');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('distincts, juridiction UE')
        ->run();
});

/*
 * La réplique et les sauvegardes, sondées pour elles-mêmes.
 *
 * « Stockage des voix » ne touche que le compartiment des médias, et c'est le
 * seul dont l'absence se voit tout de suite. Les deux autres se taisent
 * jusqu'au jour où on en a besoin : `ReplicateRecording` échoue dans un
 * ouvrier, et la sauvegarde n'existe pas au moment du désastre. Or la réplique
 * porte le SLO du doc 04 §11 — « zéro perte après "histoire enregistrée" »
 * — et un nom de compartiment se tape à la main dans un `.env` (T-228).
 */
it('ne sonde aucun compartiment voisin sans stockage réel', function () {
    /*
     * Ce qui est vérifiable ici s'arrête là, et il faut le dire : le sondage
     * écrit vraiment dans trois compartiments, donc il ne s'exerce qu'avec un
     * stockage réel — `--rapide` l'écarte, et le pilote `fake` de cette suite
     * aussi. C'est `prod:check` lui-même, sur le serveur, qui l'éprouve : la
     * commande existe précisément pour ce que les tests ne peuvent pas voir.
     */
    $this->artisan('prod:check', ['--rapide' => true])
        ->doesntExpectOutputToContain('Réplique')
        ->run();

    config()->set('services.media.driver', 'fake');

    $this->artisan('prod:check')
        ->doesntExpectOutputToContain('Réplique')
        ->run();
});

/*
 * `ffmpeg`, le premier maillon et le plus silencieux.
 *
 * Tout ce qui suit une voix en dépend : sans dérivé MP3, rien à envoyer au
 * transcripteur — donc pas de texte, pas de relecture, pas de validation,
 * rien qui atteigne la famille, pas de livre. Le narrateur, lui, a vu
 * « votre histoire est enregistrée », et c'était vrai : le stockage l'avait
 * confirmée. Elle dort là, sans suite. Et rien ne le dit — le job échoue
 * trois fois dans un ouvrier puis se range dans `failed_jobs` (T-230).
 */
it('dit ce que le client perd quand ffmpeg manque, et comment l’installer', function () {
    config()->set('product.media.ffmpeg', '/usr/bin/ffmpeg-qui-nexiste-pas');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('aucune voix ne devient du texte')
        // Le remède sur sa propre ligne, avec la reprise des travaux perdus.
        ->expectsOutputToContain('apt-get install -y ffmpeg')
        ->run();
});

it('nomme ffprobe séparément, pour ce qu’il porte', function () {
    // `ffprobe` donne la durée réelle, celle qui nourrit les critères
    // book-ready de R-6 : sans lui, on ne sait plus si la matière suffit.
    config()->set('product.media.ffprobe', '/usr/bin/ffprobe-qui-nexiste-pas');

    $this->artisan('prod:check', ['--rapide' => true])
        ->expectsOutputToContain('les critères du livre (R-6) ne peuvent pas être mesurés')
        ->run();
});

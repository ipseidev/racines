<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Metrics\H0Acceptance14d;
use App\Metrics\H1Activated;
use App\Metrics\H1Itt8StoriesJ70;
use App\Metrics\LivingProjects;
use App\Metrics\Registry;
use App\Models\Cohort;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Project;
use App\Models\Story;
use App\States\Story\Validated;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Les métriques du pilote.
 *
 * Ce sont les chiffres sur lesquels se décident les gates 0A et Phase 1. Deux
 * choses comptent donc autant que leur exactitude : **le dénominateur** —
 * c'est là qu'un chiffre s'embellit sans mentir — et **l'idempotence**, parce
 * qu'une commande planifiée finit toujours par tourner deux fois.
 */
function projetAccepte(string $accepteIlYA, ?string $cohorte = null): Project
{
    return Project::factory()->create([
        'status' => ProjectStatus::Active,
        'cohort_id' => $cohorte,
        'accepted_at' => CarbonImmutable::parse($accepteIlYA),
    ]);
}

function histoiresValidees(Project $project, int $combien, string $quand): void
{
    for ($i = 0; $i < $combien; $i++) {
        Story::factory()->create([
            'project_id' => $project->id,
            'state' => Validated::class,
            'recorded_at' => $quand,
            'validated_at' => $quand,
        ]);
    }
}

it('écrit une ligne par métrique et par cohorte', function (): void {
    // `cohort_id` est une clé étrangère : deux vraies cohortes, comme en
    // production, plutôt que deux chaînes qui passeraient en SQLite et pas
    // en Postgres.
    projetAccepte('-100 days', Cohort::factory()->create()->id);
    projetAccepte('-100 days', Cohort::factory()->create()->id);

    $this->artisan('metrics:compute', ['--date' => now()->toDateString()])->assertSuccessful();

    // Global plus deux cohortes : le pilote se lit par vague, et un chiffre
    // d'ensemble reste nécessaire pour le comité.
    expect(DB::table('daily_metrics')->distinct()->count('cohort_id'))->toBe(2)
        ->and(DB::table('daily_metrics')->count())->toBe(count(Registry::all()) * 3);
});

it('se rejoue sans rien doubler', function (): void {
    projetAccepte('-100 days');

    $this->artisan('metrics:compute', ['--date' => now()->toDateString()]);
    $avant = DB::table('daily_metrics')->count();

    $this->artisan('metrics:compute', ['--date' => now()->toDateString()]);

    // L'unicité est dans la base, pas dans le code : une commande planifiée
    // finit toujours par tourner deux fois.
    expect(DB::table('daily_metrics')->count())->toBe($avant);
});

it('recalcule une date passée', function (): void {
    projetAccepte('-100 days');

    $this->artisan('metrics:compute', ['--date' => '2026-08-01'])->assertSuccessful();

    // Une définition de métrique se corrige toujours en cours de pilote : un
    // tableau de bord qui ne se recalcule pas fige ses propres erreurs.
    expect(DB::table('daily_metrics')->where('date', '2026-08-01')->exists())->toBeTrue();
});

/*
 * H1 en intention de traiter : le critère de sortie du bloc.
 *
 * C'est le chiffre le plus facile à embellir sans mentir. Compter sur les
 * « activés » retirerait du dénominateur exactement les familles où le
 * produit a échoué le plus tôt.
 */
it('compte les accepteurs jamais activés dans le dénominateur de H1', function (): void {
    $actif = projetAccepte('-80 days');
    histoiresValidees($actif, 8, now()->subDays(70)->toDateString());

    // Un accepteur qui n'a jamais rien enregistré : il **compte**.
    projetAccepte('-80 days');

    $itt = (new H1Itt8StoriesJ70)->compute(CarbonImmutable::now(), null);

    expect($itt->denominator)->toBe(2)
        ->and($itt->numerator)->toBe(1)
        ->and($itt->value)->toBe(0.5);
});

it('sort un chiffre plus flatteur pour les activés, et c’est le but de l’écart', function (): void {
    $actif = projetAccepte('-80 days');
    histoiresValidees($actif, 8, now()->subDays(70)->toDateString());
    projetAccepte('-80 days');

    $itt = (new H1Itt8StoriesJ70)->compute(CarbonImmutable::now(), null);
    $actives = (new H1Activated)->compute(CarbonImmutable::now(), null);

    // L'écart entre les deux dit **où** le produit casse : ITT bas et activés
    // hauts, c'est l'entrée qui échoue ; les deux bas, c'est la répétition.
    expect($actives->value)->toBeGreaterThan($itt->value)
        ->and($actives->denominator)->toBe(1);
});

it('ignore les accepteurs trop récents pour avoir eu leurs soixante-dix jours', function (): void {
    projetAccepte('-10 days');

    // Les compter ferait baisser le taux à chaque vente, ce qui n'a aucun sens.
    expect((new H1Itt8StoriesJ70)->compute(CarbonImmutable::now(), null)->denominator)->toBe(0);
});

/*
 * La North Star : la boucle entière, ou rien.
 */
it('ne compte pas vivant un projet qui produit sans être écouté', function (): void {
    $project = projetAccepte('-40 days');
    histoiresValidees($project, 3, now()->subDays(5)->toDateString());

    // Trois histoires validées et personne pour les écouter : ce n'est pas
    // une famille vivante, et c'est l'illusion qu'un compteur d'histoires
    // entretiendrait.
    expect((new LivingProjects)->compute(CarbonImmutable::now(), null)->numerator)->toBe(0);
});

it('compte vivant un projet écouté trente secondes par un proche', function (): void {
    $project = projetAccepte('-40 days');
    histoiresValidees($project, 1, now()->subDays(5)->toDateString());

    $membre = FamilyMember::factory()->create(['project_id' => $project->id]);
    $story = $project->stories()->firstOrFail();

    // `reached_30s` n'est pas remplissable en masse — c'est le produit qui
    // le pose, à partir des secondes réellement écoutées.
    $event = new ListenEvent([
        'token_type' => TokenType::ListenProject,
        'seconds_listened' => 45,
        'started_at' => now(),
    ]);
    $event->story()->associate($story);
    $event->familyMember()->associate($membre);
    $event->reached_30s = true;
    $event->save();

    expect((new LivingProjects)->compute(CarbonImmutable::now(), null)->numerator)->toBe(1);
});

it('ne compte pas une écoute du narrateur qui se réécoute', function (): void {
    $project = projetAccepte('-40 days');
    histoiresValidees($project, 1, now()->subDays(5)->toDateString());

    $event = new ListenEvent([
        'token_type' => TokenType::Qr,
        'seconds_listened' => 45,
        'started_at' => now(),
    ]);
    $event->story()->associate($project->stories()->firstOrFail());
    $event->reached_30s = true;
    $event->save();

    // La North Star mesure une **boucle familiale** : sans proche au bout,
    // il n'y a pas de boucle.
    expect((new LivingProjects)->compute(CarbonImmutable::now(), null)->numerator)->toBe(0);
});

it('rend zéro et non rien quand une cohorte n’a aucune acceptation', function (): void {
    // Zéro est une information ; `null` ferait disparaître la ligne du
    // tableau, et l'absence se lit « pas encore mesuré » — le contraire.
    $valeur = (new H0Acceptance14d)->compute(CarbonImmutable::now(), Cohort::factory()->create()->id);

    expect($valeur->value)->toBe(0.0)
        ->and($valeur->denominator)->toBe(0);
});

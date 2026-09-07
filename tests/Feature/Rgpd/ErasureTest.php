<?php

declare(strict_types=1);

use App\Actions\ConfirmErasure;
use App\Actions\RequestErasure;
use App\Enums\BookStatus;
use App\Enums\ErasureScope;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketKind;
use App\Exceptions\Domain\ErasureBlocked;
use App\Jobs\EraseProject;
use App\Models\AccessToken;
use App\Models\Book;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Order;
use App\Models\Project;
use App\Models\Story;
use App\Models\SupportTicket;
use App\Models\Transcript;
use App\Models\User;
use App\States\Story\Proposed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * L'effacement.
 *
 * Le geste le plus grave du produit après l'impression, et le seul qui soit
 * **irréversible sans papier pour rattraper**. Trois règles le gouvernent, et
 * elles viennent du dossier, pas de la technique :
 *
 *  1. **Le narrateur gagne** (doc 04 §3). Si sa demande d'effacement croise
 *     une demande de conservation de l'Initiateur·rice, il n'y a pas
 *     d'arbitrage éditorial : c'est sa voix.
 *  2. **Une confirmation humaine**, jamais un clic qui exécute. Trente jours
 *     au plus, mais un humain valide — parce que personne ne récupère ce qui
 *     a été effacé par erreur à deux heures du matin.
 *  3. **Le journal survit, masqué.** Effacer la preuve de ce qui a été fait
 *     des données serait effacer le moyen de répondre à qui les réclame.
 *
 * @return array{Project, Narrator, Story}
 */
function projetEffacable(): array
{
    $project = Project::factory()->create();
    $narrator = Narrator::factory()->create([
        'project_id' => $project->id,
        'first_name' => 'Odette',
        'is_primary' => true,
    ]);

    $story = Story::factory()->shared()->create(['project_id' => $project->id]);
    Transcript::factory()->for($story)->create(['text' => 'Le mot à mot.']);

    Consent::factory()->create([
        'project_id' => $project->id,
        'subject_type' => 'narrator',
        'subject_id' => $narrator->id,
    ]);

    return [$project, $narrator, $story->refresh()];
}

it('ouvre un ticket plutôt que d’effacer sur-le-champ', function (): void {
    [$project, $narrator] = projetEffacable();

    $ticket = app(RequestErasure::class)->handle($project, ErasureScope::Narrator, $narrator);

    expect($ticket->kind)->toBe(SupportTicketKind::ErasureRequested)
        ->and($ticket->payload['scope'])->toBe('narrator');

    // Rien n'est encore effacé : personne ne récupère ce qui part par erreur.
    expect($project->refresh()->stories()->count())->toBe(1);
});

it('efface les fichiers, les textes et les champs personnels', function (): void {
    [$project, $narrator, $story] = projetEffacable();

    app()->call([new EraseProject($project->id), 'handle']);

    /*
     * `first_name` porte un marqueur et non `null` : la colonne n'est pas
     * nullable, et tout le produit la lit sans vérifier. Ce qui compte est
     * qu'**il ne reste rien de la personne** — et qu'un dump distingue « a
     * demandé l'effacement » de « champ jamais rempli ».
     */
    expect(Transcript::query()->where('story_id', $story->id)->count())->toBe(0)
        ->and($narrator->refresh()->first_name)->toBe(EraseProject::MARQUEUR)
        ->and($narrator->first_name)->not->toContain('Odette')
        ->and($narrator->phone_e164)->toBeNull()
        ->and($narrator->email)->toBeNull();
});

it('efface aussi les proches, que tout projet réel possède', function (): void {
    [$project] = projetEffacable();

    $proche = FamilyMember::factory()->create([
        'project_id' => $project->id,
        'display_name' => 'Camille',
        'email' => 'camille@exemple.fr',
        'phone_e164' => '+33600000001',
    ]);

    app()->call([new EraseProject($project->id), 'handle']);

    /*
     * `last_name` n'existe pas sur `family_members` — un proche n'a qu'un nom
     * affiché. L'écrire pour les deux faisait échouer l'effacement **entier**
     * sur un « column last_name does not exist », donc pour tout projet réel :
     * `FulfillOrder` pose la fiche d'écoute de l'acheteuse à chaque achat, et
     * aucun projet n'arrive à l'effacement sans proche. Le décor de ce fichier
     * n'en semait aucun, et le défaut a vécu jusqu'à ce que `prod:demo` ait
     * besoin d'effacer son propre décor.
     */
    expect($proche->refresh()->display_name)->toBe(EraseProject::MARQUEUR)
        ->and($proche->email)->toBeNull()
        ->and($proche->phone_e164)->toBeNull();
});

it('efface un projet dont une question attend encore sa réponse', function (): void {
    [$project] = projetEffacable();

    $proposee = Story::factory()->create(['project_id' => $project->id]);

    app()->call([new EraseProject($project->id), 'handle']);

    /*
     * `Proposed` ne mène pas à la corbeille — les états de retrait de R-4
     * s'appliquent à une parole, pas à une question posée. L'y pousser faisait
     * échouer l'effacement **entier**, donc pour tout projet vivant : une
     * question attend toujours sa réponse.
     */
    expect($proposee->refresh()->state->getValue())->toBe(Proposed::$name)
        ->and($project->refresh()->erased_at)->not->toBeNull();

    // Son lien d'enregistrement est mort avec les autres.
    expect(AccessToken::query()->where('subject_id', $proposee->id)->whereNull('revoked_at')->count())->toBe(0);
});

it('fait cesser toute sollicitation', function (): void {
    [$project] = projetEffacable();

    $project->forceFill([
        'status' => ProjectStatus::Active,
        'accepted_at' => now()->subDays(30),
        'next_prompt_at' => now()->addDay(),
    ])->save();

    app()->call([new EraseProject($project->id), 'handle']);

    /*
     * `erased_at` seul ne suffit pas : ni `prompts:dispatch-due` ni les six
     * règles du moteur ne le lisent, tous filtrant sur `active`. Un projet
     * effacé posait donc encore des questions et relançait — un narrateur dont
     * on venait de nuller les coordonnées.
     */
    expect($project->refresh()->status)->toBe(ProjectStatus::Cancelled)
        ->and($project->next_prompt_at)->toBeNull();
});

it('révoque tous les jetons du projet', function (): void {
    [$project, $narrator, $story] = projetEffacable();

    app()->call([new EraseProject($project->id), 'handle']);

    // Un lien d'écoute qui survivrait à l'effacement ouvrirait une page vide
    // — ou pire, servirait un cache.
    expect(AccessToken::query()->whereNull('revoked_at')->count())->toBe(0);
});

it('garde les consentements, sujets anonymisés', function (): void {
    [$project, $narrator] = projetEffacable();

    app()->call([new EraseProject($project->id), 'handle']);

    $consent = Consent::query()->firstOrFail();

    // La preuve d'un consentement est une obligation légale : elle survit,
    // mais elle ne doit plus désigner personne.
    expect($consent->subject_id)->not->toBe($narrator->id)
        ->and($consent->subject_id)->toHaveLength(64);
});

it('garde les commandes pour la comptabilité', function (): void {
    [$project] = projetEffacable();
    $order = Order::factory()->paid()->create(['project_id' => $project->id]);

    app()->call([new EraseProject($project->id), 'handle']);

    // Dix ans de conservation comptable : ce n'est pas nous qui décidons.
    expect(Order::query()->whereKey($order->getKey())->exists())->toBeTrue();
});

it('garde le journal d’audit, et la chaîne reste intacte', function (): void {
    [$project] = projetEffacable();

    app()->call([new EraseProject($project->id), 'handle']);

    expect(DB::table('audit_logs')->where('action', 'erased Project')->exists())->toBeTrue();

    // Effacer la preuve de ce qui a été fait des données serait effacer le
    // moyen de répondre à qui les réclame.
    $this->artisan('audit:verify')->assertSuccessful();
});

it('refuse d’effacer pendant qu’un livre est à l’impression', function (): void {
    [$project] = projetEffacable();
    Book::factory()->create(['project_id' => $project->id, 'status' => BookStatus::Ordered]);

    expect(fn () => app(ConfirmErasure::class)->handle(
        app(RequestErasure::class)->handle($project, ErasureScope::Project),
        User::factory()->create(),
    ))->toThrow(ErasureBlocked::class);

    // Et l'explication doit dire quoi faire, pas seulement que c'est refusé.
    expect($project->refresh()->stories()->count())->toBe(1);
});

it('donne la priorité au narrateur sur l’Initiateur·rice', function (): void {
    [$project, $narrator] = projetEffacable();

    app(RequestErasure::class)->handle($project, ErasureScope::Project);
    $duNarrateur = app(RequestErasure::class)->handle($project, ErasureScope::Narrator, $narrator);

    // Une seule demande vit à la fois, et c'est la sienne : le dossier
    // interdit l'arbitrage éditorial (doc 04 §3).
    $ouverts = SupportTicket::query()
        ->where('kind', SupportTicketKind::ErasureRequested->value)
        ->where('status', 'open')
        ->get();

    expect($ouverts)->toHaveCount(1)
        ->and($ouverts->first()->getKey())->toBe($duNarrateur->getKey());
});

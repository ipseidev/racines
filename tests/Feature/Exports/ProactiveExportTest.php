<?php

declare(strict_types=1);

use App\Books\AdvanceBookPrinting;
use App\Enums\BookStatus;
use App\Enums\ExportKind;
use App\Models\Book;
use App\Models\Export;
use App\Models\Narrator;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Les exports qu'on envoie sans qu'on les demande.
 *
 * C'est le cœur de R-10.2, et la partie que personne ne réclamera jamais :
 * une famille ne pense pas à télécharger ses données. Elle y pense le jour où
 * le service ferme, c'est-à-dire trop tard.
 *
 * Deux moments, choisis parce que ce sont ceux où l'oubli coûte le plus :
 * **à la livraison du livre**, quand le projet se termine et que tout le
 * monde passe à autre chose ; et **soixante jours avant la fin
 * d'hébergement**, assez tôt pour qu'une personne de quatre-vingts ans ait le
 * temps de demander de l'aide à quelqu'un.
 */
beforeEach(function (): void {
    Notification::fake();
});

function projetLivre(): Project
{
    $project = Project::factory()->create(['collection_started_at' => now()->subYear()]);
    Narrator::factory()->create(['project_id' => $project->id, 'is_primary' => true]);

    return $project->refresh();
}

it('envoie l’export complet et le pack hors-ligne à la livraison du livre', function (): void {
    $project = projetLivre();
    $book = Book::factory()->create([
        'project_id' => $project->id,
        'status' => BookStatus::Printed,
        'printed_at' => now(),
    ]);

    app(AdvanceBookPrinting::class)->handle($book, BookStatus::Delivered);

    $genres = Export::query()->where('project_id', $project->id)->pluck('kind')->all();

    // Les deux : l'un pour tout garder, l'autre pour l'écouter le jour où
    // les QR du livre se seront éteints.
    expect($genres)->toContain(ExportKind::Full)
        ->and($genres)->toContain(ExportKind::OfflinePack);
});

it('n’envoie rien à « imprimé », seulement à « livré »', function (): void {
    $project = projetLivre();
    $book = Book::factory()->create(['project_id' => $project->id, 'status' => BookStatus::Ordered]);

    app(AdvanceBookPrinting::class)->handle($book, BookStatus::Printed);

    // Le livre est chez l'imprimeur, pas chez la famille : lui écrire « voici
    // votre livre et vos données » serait faux d'une semaine.
    expect(Export::query()->count())->toBe(0);
});

it('envoie un export soixante jours avant la fin d’hébergement', function (): void {
    $project = projetLivre();
    $project->forceFill(['hosting_ends_at' => now()->addDays(50)])->save();

    $this->artisan('exports:proactive')->assertSuccessful();

    expect(Export::query()->where('project_id', $project->id)->count())->toBe(1);
});

it('n’envoie rien quand l’échéance est encore loin', function (): void {
    $project = projetLivre();
    $project->forceFill(['hosting_ends_at' => now()->addMonths(10)])->save();

    $this->artisan('exports:proactive')->assertSuccessful();

    expect(Export::query()->count())->toBe(0);
});

it('ne renvoie pas un export à une famille qui en a déjà reçu un récemment', function (): void {
    $project = projetLivre();
    $project->forceFill(['hosting_ends_at' => now()->addDays(50)])->save();

    $this->artisan('exports:proactive')->assertSuccessful();
    $this->artisan('exports:proactive')->assertSuccessful();

    // La commande passe tous les jours : sans cette garde, une famille
    // recevrait soixante courriels avant l'échéance.
    expect(Export::query()->count())->toBe(1);
});

it('ne touche pas un projet déjà effacé', function (): void {
    $project = projetLivre();
    $project->forceFill([
        'hosting_ends_at' => now()->addDays(50),
        'erased_at' => now(),
    ])->save();

    $this->artisan('exports:proactive')->assertSuccessful();

    // Il n'y a plus rien à exporter, et écrire à quelqu'un qui a demandé
    // l'effacement serait le contraire de ce qu'il a demandé.
    expect(Export::query()->count())->toBe(0);
});

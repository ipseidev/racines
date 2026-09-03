<?php

declare(strict_types=1);

use App\Audit\AuditLog;
use App\Models\Story;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;

/**
 * `audit:verify` : la chaîne tient-elle encore ?
 *
 * Le trigger protège de l'erreur et du script maladroit. Il ne protège pas de
 * qui détient les droits de le désactiver — et c'est précisément le scénario
 * qui compte, puisque la menace qu'on modélise est interne. La chaîne
 * d'empreintes est le second verrou : altérer une ligne devient détectable,
 * et la commande le dit avec l'identifiant de la rupture.
 *
 * Le test désactive le trigger pour falsifier, exactement comme le ferait
 * quelqu'un avec les droits. C'est la seule façon d'éprouver la garde qui
 * sert quand la première a sauté.
 */
function forgeAuditRow(int $id, callable $change): void
{
    DB::unprepared('alter table audit_logs disable trigger audit_logs_append_only');

    try {
        $change($id);
    } finally {
        DB::unprepared('alter table audit_logs enable trigger audit_logs_append_only');
    }
}

it('rend zéro quand la chaîne est intacte', function (): void {
    $story = Story::factory()->create();

    foreach (range(1, 5) as $n) {
        AuditLog::record('viewed Story', $story, ['n' => $n]);
    }

    $this->artisan('audit:verify')
        ->expectsOutputToContain('5 ligne(s) vérifiée(s)')
        ->assertSuccessful();
});

it('rend zéro sur un journal vide', function (): void {
    // Un journal vide n'est pas une rupture : c'est un projet qui vient de
    // naître. La commande tourne quotidiennement, y compris le premier jour.
    $this->artisan('audit:verify')->assertSuccessful();
});

it('détecte une ligne dont le contenu a été changé', function (): void {
    $story = Story::factory()->create();

    AuditLog::record('viewed Story', $story);
    AuditLog::record('played Recording', $story);
    AuditLog::record('edited Transcript', $story);

    $target = (int) DB::table('audit_logs')->orderBy('id')->skip(1)->value('id');

    forgeAuditRow($target, function (int $id): void {
        DB::table('audit_logs')->where('id', $id)->update(['action' => 'rien du tout']);
    });

    // Une seule attente, et elle nomme la ligne : les attentes de sortie de
    // Laravel se consomment **dans l'ordre**, une par ligne produite. Deux
    // attentes séparées cherchaient la seconde dans la ligne suivante, où
    // elle n'a rien à faire.
    $this->artisan('audit:verify')
        ->expectsOutputToContain("Ligne {$target} : empreinte incohérente")
        ->assertFailed();
});

it('détecte une ligne supprimée au milieu', function (): void {
    $story = Story::factory()->create();

    foreach (range(1, 4) as $n) {
        AuditLog::record('viewed Story', $story, ['n' => $n]);
    }

    $target = (int) DB::table('audit_logs')->orderBy('id')->skip(1)->value('id');

    forgeAuditRow($target, function (int $id): void {
        DB::table('audit_logs')->where('id', $id)->delete();
    });

    // Le maillon suivant pointe vers un parent qui n'existe plus : c'est
    // exactement ce que la chaîne sert à révéler.
    $this->artisan('audit:verify')
        ->expectsOutputToContain('chaînage rompu')
        ->assertFailed();
});

it('détecte un journal tronqué par le début', function (): void {
    $story = Story::factory()->create();

    foreach (range(1, 3) as $n) {
        AuditLog::record('viewed Story', $story, ['n' => $n]);
    }

    $first = (int) DB::table('audit_logs')->orderBy('id')->value('id');

    forgeAuditRow($first, function (int $id): void {
        DB::table('audit_logs')->where('id', $id)->delete();
    });

    // Sans racine connue, ce cas serait indétectable : un journal qui
    // commence à la deuxième ligne ressemblerait à un journal neuf.
    $this->artisan('audit:verify')
        ->expectsOutputToContain('ne commence pas à la racine')
        ->assertFailed();
});

it('n’examine que la période demandée', function (): void {
    $story = Story::factory()->create();

    AuditLog::record('viewed Story', $story, ['n' => 'ancienne']);
    $old = (int) DB::table('audit_logs')->value('id');

    forgeAuditRow($old, function (int $id): void {
        DB::table('audit_logs')->where('id', $id)->update([
            'occurred_at' => now()->subMonths(6)->format('Y-m-d H:i:sP'),
        ]);
    });

    AuditLog::record('viewed Story', $story, ['n' => 'récente']);

    // Bornée au mois courant : la ligne ancienne, dont l'horodatage a bougé,
    // n'est pas dans le périmètre. La commande ne parle que de ce qu'on lui
    // a demandé de regarder — et le dit.
    $this->artisan('audit:verify', ['--from' => now()->startOfMonth()->toDateString()])
        ->expectsOutputToContain('1 ligne(s) vérifiée(s)')
        ->assertSuccessful();
});

it('est planifiée quotidiennement', function (): void {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn (object $event): string => (string) $event->command);

    // Une vérification qu'il faut penser à lancer n'est pas une vérification.
    expect($commands->contains(fn (string $command): bool => str_contains($command, 'audit:verify')))
        ->toBeTrue();
});

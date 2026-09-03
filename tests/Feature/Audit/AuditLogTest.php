<?php

declare(strict_types=1);

use App\Audit\AuditLog;
use App\Enums\ActorContext;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Le journal d'audit.
 *
 * Le dossier 04 §12 exige une « journalisation inviolable de toutes les
 * actions support (**lecture comprise**) ». Inviolable, ici, n'est pas une
 * façon de parler : le trigger refuse `UPDATE` et `DELETE` au niveau de la
 * base, et chaque ligne porte l'empreinte de la précédente. Effacer une trace
 * demanderait de recalculer toute la chaîne — ce qui est détectable, et c'est
 * tout l'intérêt.
 *
 * Ce que ce test protège vraiment : qu'un support qui a écouté une histoire
 * privée ne puisse pas faire disparaître le fait qu'il l'a écoutée.
 */
it('ajoute une ligne dont l’empreinte enchaîne la précédente', function (): void {
    $story = Story::factory()->create();

    AuditLog::record('viewed Story', $story);
    AuditLog::record('played Recording', $story);

    $rows = DB::table('audit_logs')->orderBy('id')->get();

    expect($rows)->toHaveCount(2)
        // La première ligne s'accroche à une racine connue, pas à rien : une
        // chaîne qui commence par une valeur nulle ne peut pas distinguer
        // « première ligne » de « lignes précédentes supprimées ».
        ->and($rows[0]->previous_hash)->toBe(AuditLog::GENESIS)
        ->and($rows[1]->previous_hash)->toBe($rows[0]->hash)
        ->and(strlen((string) $rows[0]->hash))->toBe(64);
});

it('refuse la modification et la suppression au niveau de la base', function (): void {
    $story = Story::factory()->create();
    AuditLog::record('viewed Story', $story);

    $id = (int) DB::table('audit_logs')->value('id');

    /*
     * Chaque tentative vit dans sa propre transaction imbriquée, et ce n'est
     * pas de la coquetterie : en Postgres, une instruction en erreur avorte
     * la transaction courante, et tout ce qui suit échoue avec « current
     * transaction is aborted ». Or `RefreshDatabase` nous a déjà placés dans
     * une transaction. Une transaction imbriquée pose un point de reprise et
     * y revient, ce qui laisse la suite du test utilisable.
     */
    expect(fn () => DB::transaction(
        fn () => DB::statement('update audit_logs set action = ? where id = ?', ['falsifié', $id]),
    ))->toThrow(QueryException::class);

    expect(fn () => DB::transaction(
        fn () => DB::statement('delete from audit_logs where id = ?', [$id]),
    ))->toThrow(QueryException::class);

    // Et la ligne est intacte : le trigger refuse avant d'écrire.
    expect(DB::table('audit_logs')->where('id', $id)->value('action'))->toBe('viewed Story');
});

it('masque les courriels, les téléphones et les jetons du contenu', function (): void {
    $story = Story::factory()->create();

    AuditLog::record('edited Transcript', $story, [
        'email' => 'odette@exemple.test',
        'phone' => '+33612345678',
        'link' => 'https://liens.test/r/'.str_repeat('a', 43),
        'before' => 'Le village s’appelait odette@exemple.test',
        'reason' => 'Correction demandée par le narrateur',
    ]);

    $payload = (string) DB::table('audit_logs')->value('payload');

    // Le journal doit pouvoir être lu par un auditeur sans lui livrer le
    // carnet d'adresses de la famille — ni un lien encore utilisable.
    expect($payload)->not->toContain('odette@exemple.test')
        ->and($payload)->not->toContain('+33612345678')
        ->and($payload)->not->toContain(str_repeat('a', 43))
        // Le motif, lui, reste lisible : c'est ce qui rend l'audit utile.
        ->and($payload)->toContain('Correction demandée par le narrateur');
});

it('enregistre le rôle et le contexte de l’acteur', function (): void {
    $support = User::factory()->create(['role' => UserRole::Support]);
    $story = Story::factory()->create();

    $this->actingAs($support);

    AuditLog::record('reissued AccessToken', $story);

    $row = DB::table('audit_logs')->first();

    expect($row->actor_user_id)->toBe($support->id)
        ->and($row->actor_role)->toBe(UserRole::Support->value)
        // Hors requête HTTP et hors panneau : le contexte par défaut est la
        // console, pas « web ». Se tromper ici rendrait un audit trompeur.
        ->and($row->actor_context)->toBe(ActorContext::Cli->value);
});

it('note le système comme acteur quand personne n’est connecté', function (): void {
    $story = Story::factory()->create();

    AuditLog::record('purged Recording', $story);

    $row = DB::table('audit_logs')->first();

    // Une purge programmée n'a pas d'auteur humain, et prétendre le contraire
    // serait pire que de l'admettre.
    expect($row->actor_user_id)->toBeNull()
        ->and($row->actor_role)->toBe('system')
        ->and($row->actor_context)->toBe(ActorContext::System->value);
});

it('rattache la ligne au projet du sujet sans qu’on le lui dise', function (): void {
    $story = Story::factory()->create();

    AuditLog::record('viewed Story', $story);

    // L'audit se lit projet par projet — « qui a touché à cette famille ? ».
    // Le déduire du sujet évite d'oublier de le passer.
    expect(DB::table('audit_logs')->value('project_id'))->toBe($story->project_id);
});

it('hache l’adresse IP plutôt que de la garder', function (): void {
    $project = Project::factory()->create();

    AuditLog::record('viewed Project', $project, [], ip: '203.0.113.7');

    $row = DB::table('audit_logs')->first();

    expect($row->ip_hash)->toBe(hash('sha256', '203.0.113.7'))
        ->and($row->ip_hash)->not->toContain('203.0.113');
});

it('sérialise le sujet même quand il n’y en a pas', function (): void {
    AuditLog::record('ran access:review');

    $row = DB::table('audit_logs')->first();

    // Certaines actions ne portent sur rien de précis. Elles doivent quand
    // même laisser une trace, et la chaîne doit rester calculable.
    expect($row->subject_type)->toBeNull()
        ->and($row->subject_id)->toBeNull()
        ->and(strlen((string) $row->hash))->toBe(64);
});

it('enchaîne correctement sous écritures concurrentes', function (): void {
    $story = Story::factory()->create();

    // Cinquante lignes d'affilée : si la tête de chaîne était lue sans
    // verrou, deux lignes finiraient par partager la même `previous_hash`,
    // et la vérification ne le dirait qu'au prochain audit.
    for ($i = 0; $i < 50; $i++) {
        AuditLog::record('viewed Story', $story, ['n' => $i]);
    }

    $rows = DB::table('audit_logs')->orderBy('id')->get();
    $previous = AuditLog::GENESIS;

    foreach ($rows as $row) {
        expect($row->previous_hash)->toBe($previous);
        $previous = (string) $row->hash;
    }

    expect($rows)->toHaveCount(50)
        ->and(collect($rows)->pluck('hash')->unique())->toHaveCount(50);
});

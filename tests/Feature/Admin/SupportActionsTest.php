<?php

declare(strict_types=1);

use App\Actions\IssueRefund;
use App\Audit\AuditLog;
use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\Order;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Payments\FakeRefunds;
use App\Services\Payments\Refunds;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

/**
 * Les gestes du support, et ce qu'ils laissent derrière eux.
 *
 * Chaque action passe par une action du domaine et écrit au journal. Le
 * remboursement passe en plus par un **port** : sans lui, un test qui aurait
 * oublié un doublon aurait pu rembourser un vrai paiement — l'erreur la plus
 * coûteuse que ce dépôt puisse commettre (T-105).
 */
function fakeRefunds(): FakeRefunds
{
    $refunds = new FakeRefunds;
    app()->instance(Refunds::class, $refunds);

    return $refunds;
}

function paidOrderFor(User $buyer, array $overrides = []): Order
{
    $project = Project::factory()->create(['owner_user_id' => $buyer->id]);

    $order = new Order(array_merge([
        'stripe_checkout_session_id' => 'cs_test_'.uniqid(),
        'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
        'status' => OrderStatus::Paid,
        'subtotal_cents' => 4_900,
        'total_cents' => 4_900,
        'paid_at' => now(),
        'withdrawal_deadline_at' => now()->addDays(14),
    ], $overrides));

    $order->user()->associate($buyer);
    $order->project()->associate($project);
    $order->save();

    return $order->refresh();
}

it('rembourse par le port, jamais par le réseau', function (): void {
    $refunds = fakeRefunds();
    $admin = User::factory()->admin()->create();
    $order = paidOrderFor(User::factory()->create());

    $this->actingAs($admin);

    app(IssueRefund::class)->handle($order, 2_500, 'Option téléphone non assurée');

    expect($refunds->last())->toBe([
        'payment_intent' => $order->stripe_payment_intent_id,
        'amount' => 2_500,
        'reason' => 'Option téléphone non assurée',
    ]);
});

it('n’écrit pas l’état de la commande : c’est le webhook qui le fait', function (): void {
    fakeRefunds();
    $admin = User::factory()->admin()->create();
    $order = paidOrderFor(User::factory()->create());

    $this->actingAs($admin);

    app(IssueRefund::class)->handle($order, 4_900, 'Le narrateur a préféré ne pas participer');

    // Deux chemins qui écrivent la même colonne divergeraient le jour où
    // quelqu'un rembourse depuis le tableau de bord de Stripe.
    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->refunded_cents)->toBe(0);
});

it('inscrit le motif du remboursement au journal', function (): void {
    fakeRefunds();
    $admin = User::factory()->admin()->create();
    $order = paidOrderFor(User::factory()->create());

    $this->actingAs($admin);

    app(IssueRefund::class)->handle($order, 4_900, 'Le narrateur a préféré ne pas participer');

    $row = DB::table('audit_logs')->first();

    // Un remboursement sans motif est un mouvement d'argent qu'on ne peut pas
    // expliquer trois mois plus tard.
    expect($row->action)->toBe('refunded Order')
        ->and($row->actor_user_id)->toBe($admin->id)
        ->and((string) $row->payload)->toContain('préféré ne pas participer')
        ->and($row->project_id)->toBe($order->project?->id);
});

it('refuse un montant au-delà de ce qui reste remboursable', function (): void {
    fakeRefunds();
    $order = paidOrderFor(User::factory()->create(), ['refunded_cents' => 4_000]);

    expect(fn () => app(IssueRefund::class)->handle($order, 1_500, 'Trop'))
        ->toThrow(RuntimeException::class);

    expect(fn () => app(IssueRefund::class)->handle($order, 0, 'Zéro'))
        ->toThrow(RuntimeException::class);

    // Neuf cents restaient : celui-là passe.
    app(IssueRefund::class)->handle($order, 900, 'Le reste');

    expect(DB::table('audit_logs')->count())->toBe(1);
});

it('refuse de rembourser une commande sans paiement', function (): void {
    fakeRefunds();
    $order = paidOrderFor(User::factory()->create(), ['stripe_payment_intent_id' => null]);

    expect(fn () => app(IssueRefund::class)->handle($order, null, 'Sans paiement'))
        ->toThrow(RuntimeException::class);
});

it('réserve le remboursement à qui en a la permission', function (): void {
    // Rembourser est le seul geste du back-office qui déplace de l'argent.
    expect(User::factory()->support()->create()->can('refunds.issue'))->toBeFalse()
        ->and(User::factory()->supportReadonly()->create()->can('refunds.issue'))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('refunds.issue'))->toBeTrue();
});

it('clôt un ticket avec sa note', function (): void {
    $support = User::factory()->support()->create();
    $project = Project::factory()->create();

    $ticket = SupportTicket::factory()->create([
        'project_id' => $project->id,
        'kind' => SupportTicketKind::MicDeniedTwice,
        'status' => SupportTicketStatus::Open,
    ]);

    $this->actingAs($support);

    $ticket->forceFill([
        'status' => SupportTicketStatus::Closed,
        'closed_at' => now(),
        'closed_by_user_id' => $support->id,
        'payload' => ['closing_note' => 'Lien réémis, la narratrice a enregistré'],
    ])->save();

    AuditLog::record('closed SupportTicket', $ticket, [
        'note' => 'Lien réémis, la narratrice a enregistré',
    ], $project);

    expect($ticket->refresh()->isOpen())->toBeFalse()
        ->and($ticket->closed_by_user_id)->toBe($support->id)
        // La note se relit si la même famille revient : elle évite de
        // repartir de zéro.
        ->and((string) DB::table('audit_logs')->value('payload'))->toContain('Lien réémis');
});

it('gèle un projet et arrête tout ce qui devait partir', function (): void {
    $support = User::factory()->support()->create();
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'next_prompt_at' => now()->addDays(3),
    ]);

    $this->actingAs($support);

    $project->status = ProjectStatus::FrozenBereavement;
    $project->next_prompt_at = null;
    $project->save();

    AuditLog::record('froze Project', $project, [
        'reason' => 'La fille de la narratrice nous a écrit',
    ]);

    // Geler et attendre ne coûte rien ; continuer à envoyer coûte très cher.
    expect($project->refresh()->status)->toBe(ProjectStatus::FrozenBereavement)
        ->and($project->next_prompt_at)->toBeNull();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($project->refresh()->stories()->count())->toBe(0);
});

it('n’écrit jamais en base directement depuis le panneau', function (): void {
    /*
     * Critère de sortie du bloc 11 : toute action de Filament passe par une
     * action du domaine. La raison n'est pas l'élégance — c'est que les
     * invariants du produit (machine d'états, révocation de jetons,
     * journalisation) vivent dans ces actions, et qu'un `->save()` posé
     * directement les contourne tous.
     *
     * Les exceptions sont nommées : `forceFill(...)->save()` sur un ticket et
     * sur le statut d'un projet gelé, deux écritures qui ne portent aucun
     * invariant et pour lesquelles une action du domaine serait une coquille
     * vide.
     */
    $allowed = [
        'Resources/SupportTickets/SupportTicketResource.php',
        'Resources/Projects/Pages/ViewProject.php',
    ];

    $offenders = [];

    foreach (Finder::create()
        ->files()
        ->in(base_path('app/Filament'))
        ->name('*.php') as $file) {
        $contents = $file->getContents();
        $writes = preg_match('/->(save|update|delete|create)\(/', $contents) === 1;

        if ($writes && ! in_array($file->getRelativePathname(), $allowed, true)) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([], 'Écritures directes dans le panneau : '.implode(', ', $offenders));
});

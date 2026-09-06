<?php

declare(strict_types=1);

use App\Books\FulfillExtraCopies;
use App\Books\OrderExtraCopies;
use App\Enums\BookStatus;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketKind;
use App\Models\Book;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Payments\CheckoutSessions;
use App\Services\Payments\FakeCheckoutSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Les exemplaires supplémentaires.
 *
 * Ils ne passent pas par le complément de commande (T-184), et la différence
 * n'est pas technique : celui-ci refuse de vendre deux fois le même article,
 * ce qui est juste pour l'option téléphone et faux ici. Une famille commande
 * trois exemplaires en janvier et deux de plus en juin parce qu'un cousin
 * s'est manifesté — c'est le cas normal.
 */
function livreCommande(): Book
{
    Storage::fake('r2');

    $owner = User::factory()->create();
    $project = Project::factory()->create([
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active,
    ]);

    return Book::factory()->create([
        'project_id' => $project->id,
        'status' => BookStatus::Ordered,
        'proof_pdf_path' => 'books/x/proof-v1.pdf',
        'proof_version' => 1,
        'ordered_at' => now(),
    ]);
}

beforeEach(function (): void {
    $this->sessions = new FakeCheckoutSessions;
    app()->instance(CheckoutSessions::class, $this->sessions);
    config()->set('services.stripe.prices.extra_copy', 'price_extra');
});

it('ouvre un paiement de n exemplaires', function (): void {
    $book = livreCommande();

    app(OrderExtraCopies::class)->handle($book, 3);

    $session = $this->sessions->last();

    expect($session['line_items'][0]['quantity'])->toBe(3)
        ->and($session['line_items'][0]['price'])->toBe('price_extra')
        // `book_id` et non `order_id` : ce qui s'ouvre est un second tirage,
        // pas un complément de la commande initiale.
        ->and($session['metadata']['book_id'])->toBe((string) $book->getKey())
        ->and($session['metadata']['quantity'])->toBe('3');
});

it('refuse avant que le livre ne soit commandé', function (): void {
    $book = livreCommande();
    $book->forceFill(['status' => BookStatus::Proofing])->save();

    expect(fn () => app(OrderExtraCopies::class)->handle($book, 1))
        ->toThrow(RuntimeException::class);
});

it('borne la quantité en une fois', function (): void {
    $book = livreCommande();

    // Au-delà, c'est une commande qui mérite un échange : un tirage familial
    // de vingt exemplaires ne se traite pas comme un rattrapage.
    expect(fn () => app(OrderExtraCopies::class)->handle($book, 6))
        ->toThrow(RuntimeException::class);

    expect(fn () => app(OrderExtraCopies::class)->handle($book, 0))
        ->toThrow(RuntimeException::class);
});

it('ouvre un tirage à la réception du paiement', function (): void {
    $book = livreCommande();

    app(FulfillExtraCopies::class)->handle([
        'id' => 'cs_test_1',
        'metadata' => ['book_id' => (string) $book->getKey(), 'quantity' => '2'],
    ]);

    $ticket = SupportTicket::query()->where('kind', SupportTicketKind::PrintOrder->value)->firstOrFail();

    expect($ticket->payload['extra_copies'])->toBe(2)
        ->and($ticket->payload['book_id'])->toBe($book->getKey())
        ->and($ticket->payload['proof_url'])->toBeString();
});

it('ne rejoue pas un webhook deux fois', function (): void {
    $book = livreCommande();
    $session = [
        'id' => 'cs_test_1',
        'metadata' => ['book_id' => (string) $book->getKey(), 'quantity' => '2'],
    ];

    app(FulfillExtraCopies::class)->handle($session);
    app(FulfillExtraCopies::class)->handle($session);

    // Stripe rejoue au moindre doute : sans garde, une commande produirait
    // deux tirages.
    expect(SupportTicket::query()->count())->toBe(1);
});

it('ouvre deux tirages pour deux commandes distinctes', function (): void {
    $book = livreCommande();

    foreach (['cs_test_1', 'cs_test_2'] as $id) {
        app(FulfillExtraCopies::class)->handle([
            'id' => $id,
            'metadata' => ['book_id' => (string) $book->getKey(), 'quantity' => '1'],
        ]);
    }

    // Les fondre en un seul ferait imprimer la seconde commande à la place de
    // la première.
    expect(SupportTicket::query()->count())->toBe(2);
});

it('consigne sans rien créer quand le livre est introuvable', function (): void {
    livreCommande();

    $ticket = app(FulfillExtraCopies::class)->handle([
        'id' => 'cs_test_1',
        'metadata' => ['book_id' => '01a00000-0000-7000-8000-000000000000', 'quantity' => '1'],
    ]);

    expect($ticket)->toBeNull()
        ->and(SupportTicket::query()->count())->toBe(0);
});

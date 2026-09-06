<?php

declare(strict_types=1);

use App\Books\ApproveBookProof;
use App\Enums\BookStatus;
use App\Enums\SupportTicketKind;
use App\Exceptions\Domain\ProofNotApprovable;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Story;
use App\Models\SupportTicket;
use App\Models\User;
use App\States\Story\InBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * L'accord à l'impression.
 *
 * C'est le seul geste irréparable du produit : après, il y a trente
 * exemplaires chez une famille, et une coquille sur le nom d'un aïeul ne se
 * corrige plus. D'où deux cases obligatoires, jamais pré-cochées, et un
 * verrouillage de la sélection au moment de l'accord.
 */
function livreAvecBat(): Book
{
    Storage::fake('r2');

    $story = Story::factory()->shared()->create();
    $book = Book::factory()->create([
        'project_id' => $story->project_id,
        'status' => BookStatus::Proofing,
        'proof_pdf_path' => 'books/x/proof-v1.pdf',
        'proof_version' => 1,
    ]);

    $chapter = new BookChapter(['position' => 10, 'included' => true]);
    $chapter->story()->associate($story);
    $chapter->book()->associate($book);
    $chapter->save();

    return $book->refresh();
}

it('refuse l’accord si une seule case est cochée', function (): void {
    $book = livreAvecBat();
    $user = User::factory()->create();

    expect(fn () => app(ApproveBookProof::class)->handle($book, $user, true, false))
        ->toThrow(ProofNotApprovable::class);

    expect(fn () => app(ApproveBookProof::class)->handle($book, $user, false, true))
        ->toThrow(ProofNotApprovable::class);

    expect($book->refresh()->status)->toBe(BookStatus::Proofing)
        ->and($book->proof_approved_at)->toBeNull();
});

it('refuse l’accord sans bon à tirer', function (): void {
    $book = livreAvecBat();
    $book->forceFill(['proof_pdf_path' => null])->save();

    expect(fn () => app(ApproveBookProof::class)->handle($book, User::factory()->create(), true, true))
        ->toThrow(ProofNotApprovable::class);
});

it('approuve, commande, et verrouille la sélection', function (): void {
    $book = livreAvecBat();
    $user = User::factory()->create();

    $book = app(ApproveBookProof::class)->handle($book, $user, true, true);

    expect($book->status)->toBe(BookStatus::Ordered)
        ->and($book->proof_approved_at)->not->toBeNull()
        ->and($book->proof_approved_by_user_id)->toBe($user->getKey())
        ->and($book->ordered_at)->not->toBeNull()
        ->and($book->print_order_ref)->toStartWith('ticket:')
        // Ce que la famille a approuvé est ce qui sera imprimé : la sélection
        // ne bouge plus.
        ->and($book->isEditable())->toBeFalse();
});

it('ouvre un ticket d’impression avec le bon à tirer', function (): void {
    $book = livreAvecBat();

    app(ApproveBookProof::class)->handle($book, User::factory()->create(), true, true);

    $ticket = SupportTicket::query()->where('kind', SupportTicketKind::PrintOrder->value)->firstOrFail();

    expect($ticket->payload['book_id'])->toBe($book->getKey())
        ->and($ticket->payload['proof_version'])->toBe(1)
        ->and($ticket->payload['trim_size_mm'])->toBe([160, 240])
        // Un lien temporaire, jamais permanent : le livre d'une famille ne
        // traîne pas dans un ticket de support.
        ->and($ticket->payload['proof_url'])->toBeString();
});

it('fait entrer les histoires incluses au livre', function (): void {
    $book = livreAvecBat();
    $story = $book->chapters->first()->story;

    app(ApproveBookProof::class)->handle($book, User::factory()->create(), true, true);
    $story->refresh();

    expect($story->state)->toBeInstanceOf(InBook::class)
        // Le drapeau survit à un retrait ultérieur : l'état peut repartir,
        // le fait d'avoir été imprimé ne se défait pas.
        ->and($story->printed_in_book)->toBeTrue();
});

it('inscrit l’accord au journal d’audit', function (): void {
    $book = livreAvecBat();

    app(ApproveBookProof::class)->handle($book, User::factory()->create(), true, true);

    $trace = DB::table('audit_logs')->where('action', 'approved BookProof')->first();

    expect($trace)->not->toBeNull()
        ->and(json_decode((string) $trace->payload, true)['chapters'])->toBe(1);
});

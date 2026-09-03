<?php

declare(strict_types=1);

use App\Enums\BookFormat;
use App\Enums\ProjectStatus;
use App\Models\Book;
use App\Models\Project;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

/**
 * `books:evaluate` : la sortie honorable, tous les jours.
 *
 * Trois choses s'y jouent, dans cet ordre d'importance. Le livre **naît** à la
 * première histoire validée, parce qu'une famille doit pouvoir voir sa jauge
 * dès qu'elle a raconté quelque chose. À M+12 sans bon à tirer, le format
 * adapté est proposé et **une** prolongation de trois mois est posée — une
 * seule, incluse, et jamais deux. À M+15, le projet devient dormant avec un
 * crédit d'impression de vingt-quatre mois : ce n'est pas un abandon, c'est
 * une porte laissée ouverte.
 *
 * Ce que la commande ne fait **jamais** : proposer « rien ». Une famille qui a
 * raconté trois histoires a raconté trois histoires de plus que si on ne lui
 * avait rien demandé.
 */
beforeEach(function (): void {
    Notification::fake();
});

it('crée le livre à la première histoire validée', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    storyWithWords($project, 400, 'childhood');

    $this->artisan('books:evaluate')->assertSuccessful();

    $book = Book::query()->where('project_id', $project->id)->firstOrFail();

    // Le format proposé existe dès le premier jour : la jauge doit dire
    // quelque chose, pas attendre un seuil.
    expect($book->proposed_format)->toBe(BookFormat::FoundingChapter)
        ->and($book->page_count_estimate)->toBeGreaterThan(0);
});

it('ne crée rien pour un projet sans histoire validée', function (): void {
    Project::factory()->create(['status' => ProjectStatus::Active]);

    $this->artisan('books:evaluate')->assertSuccessful();

    expect(Book::query()->count())->toBe(0);
});

it('pose la date de maturité une seule fois', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    foreach (['childhood', 'work', 'love', 'places', 'beliefs_values'] as $theme) {
        foreach (range(1, 2) as $n) {
            storyWithWords($project, 1_800, $theme, 10);
        }
    }

    $this->artisan('books:evaluate');
    $premiere = Book::query()->firstOrFail()->book_ready_at;

    $this->travel(2)->days();
    $this->artisan('books:evaluate');

    // La date de maturité est un fait, pas un état : la repousser chaque jour
    // rendrait impossible de dire quand la famille est devenue prête.
    expect(Book::query()->firstOrFail()->book_ready_at?->toIso8601String())
        ->toBe($premiere?->toIso8601String());
});

it('propose le format et accorde une prolongation à M+12', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'collection_started_at' => now()->subMonths(12)->subDay(),
    ]);
    storyWithWords($project, 4_000, 'childhood');

    $this->artisan('books:evaluate')->assertSuccessful();

    $book = Book::query()->firstOrFail();

    expect($book->proposed_format)->toBe(BookFormat::Booklet)
        ->and($book->extension_granted_at)->not->toBeNull();
});

it('n’accorde jamais deux prolongations', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'collection_started_at' => now()->subMonths(12)->subDay(),
    ]);
    storyWithWords($project, 4_000, 'childhood');

    $this->artisan('books:evaluate');
    $premiere = Book::query()->firstOrFail()->extension_granted_at;

    $this->travel(10)->days();
    $this->artisan('books:evaluate');

    // Une prolongation incluse, puis payante (PRD §10). La seconde se vend,
    // elle ne se donne pas — et la donner en silence chaque jour reviendrait
    // à ne jamais finir.
    expect(Book::query()->firstOrFail()->extension_granted_at?->toIso8601String())
        ->toBe($premiere?->toIso8601String());
});

it('rend le projet dormant à M+15, avec un crédit d’impression', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'collection_started_at' => now()->subMonths(15)->subDay(),
    ]);
    storyWithWords($project, 2_000, 'childhood');

    $this->artisan('books:evaluate')->assertSuccessful();

    $book = Book::query()->firstOrFail();

    // Ce n'est pas un abandon : c'est une porte laissée ouverte deux ans.
    expect($project->refresh()->status)->toBe(ProjectStatus::Dormant)
        ->and($book->print_credit_expires_at?->toDateString())
        ->toBe(now()->addMonths(24)->toDateString());
});

it('laisse tranquille un projet dont le bon à tirer est déjà approuvé', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'collection_started_at' => now()->subMonths(16),
    ]);
    storyWithWords($project, 2_000, 'childhood');

    Book::factory()->approved()->create(['project_id' => $project->id]);

    $this->artisan('books:evaluate')->assertSuccessful();

    // Le calendrier de la sortie honorable ne s'applique qu'aux projets qui
    // n'ont pas abouti. Rendre dormant un projet dont le livre est parti à
    // l'impression serait absurde.
    expect($project->refresh()->status)->toBe(ProjectStatus::Active)
        ->and(Book::query()->firstOrFail()->print_credit_expires_at)->toBeNull();
});

it('ignore les projets gelés par un deuil', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::FrozenBereavement,
        'collection_started_at' => now()->subMonths(16),
    ]);
    storyWithWords($project, 2_000, 'childhood');

    $this->artisan('books:evaluate')->assertSuccessful();

    // Un gel arrête **tout**, y compris les échéances qui produiraient un
    // message à une famille en deuil.
    expect($project->refresh()->status)->toBe(ProjectStatus::FrozenBereavement)
        ->and(Book::query()->count())->toBe(0);
});

it('est planifiée quotidiennement', function (): void {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn (object $event): string => (string) $event->command);

    expect($commands->contains(fn (string $c): bool => str_contains($c, 'books:evaluate')))
        ->toBeTrue();
});

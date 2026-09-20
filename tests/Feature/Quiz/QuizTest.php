<?php

declare(strict_types=1);

use App\Actions\FulfillOrder;
use App\Books\RenderBookHtml;
use App\Enums\BookCover;
use App\Enums\BookTitle;
use App\Enums\Channel;
use App\Enums\QuestionTheme;
use App\Enums\QuizAgeBand;
use App\Enums\QuizDistance;
use App\Enums\QuizOccasion;
use App\Enums\QuizRelationship;
use App\Enums\QuizStorytellerStyle;
use App\Enums\TechComfort;
use App\Models\Book;
use App\Models\CheckoutDraft;
use App\Models\Lead;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\User;
use App\Settings\PilotSettings;
use App\Support\Drafts;
use App\Support\LocalizedRoutes;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

/*
 * Le tunnel de découverte : treize écrans, puis un aperçu.
 *
 * Ce qu'il protège, et qui n'est pas cosmétique : les réponses ne sont pas
 * jetées. Chacune remplit le brouillon de commande — le lien de parenté, le
 * prénom, l'aisance avec un téléphone, le canal, la date — et le tunnel
 * d'achat s'ouvre ensuite à la première étape qui manque encore, jamais à la
 * première tout court.
 *
 * Le second invariant est l'aperçu : la question qu'on montre est une vraie
 * question du corpus, de difficulté 1, choisie dans les thèmes cochés. Une
 * maquette inventée ferait promettre autre chose que ce qui partira.
 */

/** @param  array<string, mixed>  $overrides */
function quizAnswers(array $overrides = []): array
{
    return array_merge([
        'relationship' => QuizRelationship::Mother->value,
        'age_band' => QuizAgeBand::Seventies->value,
        'distance' => QuizDistance::FewHours->value,
        'themes' => [
            QuestionTheme::Childhood->value,
            QuestionTheme::Work->value,
            QuestionTheme::Legacy->value,
        ],
        'storyteller' => QuizStorytellerStyle::NeedsNudge->value,
        'tech_comfort' => TechComfort::Sometimes->value,
        'channel' => Channel::Sms->value,
        'first_name' => 'Jeanne',
        'nickname' => 'Mamie',
        'book_cover' => BookCover::Forest->value,
        'book_title' => BookTitle::Stories->value,
        'occasion' => QuizOccasion::Birthday->value,
        'send_at' => now()->addDays(20)->toDateString(),
    ], $overrides);
}

/*
 * Les textes attendus viennent du **corpus réel** (annexe A), chargé par
 * `ReferenceDataSeeder` : l'aperçu n'a d'intérêt que s'il montre ce qui
 * partira vraiment, et un corpus de test le prouverait moins bien.
 */
const OUVERTURE = 'Où êtes-vous né·e, et que vous a-t-on raconté sur le jour de votre naissance ?';

it('ouvre le premier écran sans compte ni brouillon', function (): void {
    $this->get(LocalizedRoutes::route('quiz'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Quiz')
            ->where('preview', null)
            // Les choix viennent du serveur : une liste écrite en dur dans le
            // composant ne serait traduite nulle part.
            ->has('relationships', count(QuizRelationship::cases()))
            ->has('themes', count(QuestionTheme::cases()))
        );
});

it('range chaque réponse dans le brouillon de commande', function (): void {
    $response = $this->post(route('quiz.store'), quizAnswers());

    $response->assertRedirect(LocalizedRoutes::route('quiz'));

    $draft = CheckoutDraft::query()->sole();

    expect($draft->value('for'))->toBe('relative')
        ->and($draft->value('narrator_first_name'))->toBe('Jeanne')
        ->and($draft->value('narrator_tech_comfort'))->toBe(TechComfort::Sometimes->value)
        ->and($draft->value('preferred_channel'))->toBe(Channel::Sms->value)
        ->and($draft->value('gift_send_at'))->toBe(now()->addDays(20)->toDateString())
        ->and($draft->value('gift_send_time'))->toBe(sprintf('%02d:00', app(PilotSettings::class)->gift_send_hour))
        ->and($draft->value('quiz.themes'))->toBe([
            QuestionTheme::Childhood->value,
            QuestionTheme::Work->value,
            QuestionTheme::Legacy->value,
        ])
        ->and($draft->value('quiz.nickname'))->toBe('Mamie')
        // La couverture n'est pas une réponse de quiz : c'est un champ de la
        // commande, au même titre que le canal. `FulfillOrder` la pose sur le
        // projet, le BAT l'imprime.
        ->and($draft->value('book_cover'))->toBe(BookCover::Forest->value)
        ->and($draft->value('book_title'))->toBe(BookTitle::Stories->value)
        ->and($draft->value('quiz.completed_at'))->not->toBeNull();
});

it('écrit le lien de parenté en toutes lettres, dans la langue de la page', function (): void {
    $this->post(route('quiz.store'), quizAnswers());

    expect(CheckoutDraft::query()->sole()->value('relationship'))
        ->toBe(__('enums.quiz_relationship.mother'));
});

it('laisse le mot personnel à écrire : c’est lui qui décide', function (): void {
    $this->post(route('quiz.store'), quizAnswers());

    // Le préremplir ferait sauter l'étape 3, et personne n'écrirait jamais le
    // seul texte du tunnel que le narrateur lira vraiment.
    expect(CheckoutDraft::query()->sole()->value('gift_message'))->toBeNull();
});

it('montre en aperçu une vraie question du corpus, de difficulté 1', function (): void {
    $this->post(route('quiz.store'), quizAnswers());

    $this->withCookie(Drafts::COOKIE, CheckoutDraft::query()->sole()->id)
        ->get(LocalizedRoutes::route('quiz'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('preview.firstName', 'Jeanne')
            ->where('preview.nickname', 'Mamie')
            ->where('preview.cover', BookCover::Forest->value)
            ->where('preview.title', BookTitle::Stories->value)
            ->where('preview.first', OUVERTURE)
            // Les suivantes viennent des thèmes cochés, une par thème, dans
            // l'ordre du corpus à l'intérieur de chacun.
            ->where('preview.next', [
                'Quel est votre tout premier souvenir ?',
                'Racontez votre premier jour de travail.',
                'Quel conseil donneriez-vous à votre petit-fils ou votre petite-fille pour ses dix-huit ans ?',
            ])
        );
});

it('ouvre toujours sur une question facile, même si l’enfance n’est pas cochée', function (): void {
    $this->post(route('quiz.store'), quizAnswers([
        'themes' => [
            QuestionTheme::Work->value,
            QuestionTheme::Legacy->value,
            QuestionTheme::FamilyOrigins->value,
        ],
    ]));

    $this->withCookie(Drafts::COOKIE, CheckoutDraft::query()->sole()->id)
        ->get(LocalizedRoutes::route('quiz'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Règle 1 du corpus : une première question intime fait
            // raccrocher. L'ouverture reste de difficulté 1, donc de l'enfance.
            ->where('preview.first', OUVERTURE)
        );
});

it('refuse moins de trois thèmes', function (): void {
    $this->post(route('quiz.store'), quizAnswers([
        'themes' => [QuestionTheme::Childhood->value],
    ]))->assertInvalid(['themes']);

    expect(CheckoutDraft::query()->count())->toBe(0);
});

it('refuse une couverture qui n’existe pas', function (): void {
    // La contrainte existe aussi en base sur `projects.book_cover` : ce qui
    // part à l'impression est une liste fermée de matières, pas une chaîne.
    $this->post(route('quiz.store'), quizAnswers([
        'book_cover' => 'leopard',
    ]))->assertInvalid(['book_cover']);
});

it('exige le titre libre quand c’est celui qu’on a demandé', function (): void {
    $this->post(route('quiz.store'), quizAnswers([
        'book_title' => BookTitle::Custom->value,
    ]))->assertInvalid(['book_title_custom']);

    $this->post(route('quiz.store'), quizAnswers([
        'book_title' => BookTitle::Custom->value,
        'book_title_custom' => 'Les dimanches chez Mamie',
    ]))->assertValid();

    expect(CheckoutDraft::query()->sole()->value('book_title_custom'))
        ->toBe('Les dimanches chez Mamie');
});

it('oublie un titre libre saisi puis abandonné', function (): void {
    // Sinon il resterait en base et referait surface le jour où quelqu'un
    // rebasculerait sur le titre libre depuis son espace.
    $this->post(route('quiz.store'), quizAnswers([
        'book_title' => BookTitle::Story->value,
        'book_title_custom' => 'Un titre que je ne veux plus',
    ]));

    expect(CheckoutDraft::query()->sole()->value('book_title_custom'))->toBeNull();
});

it('refuse un thème qui n’existe pas', function (): void {
    $this->post(route('quiz.store'), quizAnswers([
        'themes' => ['childhood', 'work', 'recettes-de-cuisine'],
    ]))->assertInvalid(['themes.2']);
});

it('refuse une date d’envoi passée ou trop lointaine', function (): void {
    $this->post(route('quiz.store'), quizAnswers([
        'send_at' => now()->subDay()->toDateString(),
    ]))->assertInvalid(['send_at']);

    $this->post(route('quiz.store'), quizAnswers([
        'send_at' => now()->addDays(120)->toDateString(),
    ]))->assertInvalid(['send_at']);
});

it('envoie vers l’étape du tunnel qui manque encore, jamais vers la première', function (): void {
    $this->post(route('quiz.store'), quizAnswers());

    $this->withCookie(Drafts::COOKIE, CheckoutDraft::query()->sole()->id)
        ->get(LocalizedRoutes::route('quiz'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // L'étape 2 : le quiz sait le prénom et le canal, pas encore le
            // numéro ni l'adresse.
            ->where('checkoutUrl', LocalizedRoutes::route('checkout.show', ['step' => 2]))
        );
});

it('reprend le brouillon du quiz dans le tunnel', function (): void {
    $this->post(route('quiz.store'), quizAnswers());

    $draft = CheckoutDraft::query()->sole();

    $this->withCookie(Drafts::COOKIE, $draft->id)
        ->get(LocalizedRoutes::route('checkout.show', ['step' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('step', 2)
            ->where('draft.narrator_first_name', 'Jeanne')
            ->where('draft.narrator_tech_comfort', TechComfort::Sometimes->value)
        );
});

it('recommencer efface les réponses sans toucher au reste du brouillon', function (): void {
    $this->post(route('quiz.store'), quizAnswers());

    $id = CheckoutDraft::query()->sole()->id;

    $this->withCookie(Drafts::COOKIE, $id)->delete(route('quiz.restart'));

    expect(CheckoutDraft::query()->sole()->value('quiz'))->toBeNull()
        // Ce que le quiz a écrit dans la commande, lui, reste : « refaire le
        // questionnaire » ne demande pas qu'on jette le brouillon.
        ->and(CheckoutDraft::query()->sole()->value('narrator_first_name'))->toBe('Jeanne');

    $this->withCookie(Drafts::COOKIE, $id)
        ->get(LocalizedRoutes::route('quiz'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('preview', null));
});

it('garde l’adresse laissée à l’aperçu, et n’affiche jamais le code', function (): void {
    Notification::fake();
    $this->post(route('quiz.store'), quizAnswers());

    $response = $this->post(route('quiz.email'), [
        'email' => 'claire@exemple.test',
        'news' => false,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    $lead = Lead::query()->sole();

    expect($lead->email)->toBe('claire@exemple.test')
        ->and($lead->source)->toBe(Lead::SOURCE_QUIZ)
        // La case des nouvelles est distincte, et décochée par défaut.
        ->and($lead->news_opted_in_at)->toBeNull();

    $this->withCookie(Drafts::COOKIE, CheckoutDraft::query()->sole()->id)
        ->get(LocalizedRoutes::route('quiz'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('emailSaved', true))
        ->assertDontSee($lead->discount_code);
});

it('répond « merci » au robot qui remplit le champ invisible', function (): void {
    Notification::fake();

    $this->post(route('quiz.email'), [
        'email' => 'robot@exemple.test',
        'website' => 'https://exemple.test',
    ])->assertRedirect();

    expect(Lead::query()->count())->toBe(0);
});

it('laisse la page s’indexer, et pas la variante du tunnel', function (): void {
    $html = $this->get(LocalizedRoutes::route('quiz'))->getContent();

    expect($html)->not->toContain('noindex')
        ->and($html)->toContain('rel="canonical" href="'.LocalizedRoutes::route('quiz').'"');
});

it('porte la couverture choisie jusqu’au projet payé', function (): void {
    $this->post(route('quiz.store'), quizAnswers([
        'book_cover' => BookCover::Plum->value,
    ]));

    $draft = CheckoutDraft::query()->sole();

    // Le reste du tunnel, celui que le quiz ne remplit pas.
    $draft->merge([
        'narrator_email' => 'jeanne@exemple.test',
        'address_form' => 'vous',
        'gift_message' => 'J’aimerais garder tes histoires.',
        'gift_variant' => 'ecard',
        'extra_copies' => 0,
        'accepts_terms' => true,
    ]);

    $buyer = User::factory()->create();

    app(FulfillOrder::class)->handle([
        'id' => 'cs_test_cover',
        'payment_intent' => 'pi_test_cover',
        'amount_total' => 8_900,
        'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
    ]);

    expect(Project::query()->sole()->book_cover)->toBe(BookCover::Plum)
        ->and(Project::query()->sole()->book_title)->toBe(BookTitle::Stories);
});

it('imprime la couverture choisie sur le BAT', function (): void {
    /*
     * Le bout de la chaîne, et la raison d'être de tout l'écran : la teinte
     * tapée sur un téléphone en septembre est celle que l'imprimeur reçoit.
     * Sans ce test, le choix resterait ce qu'il est chez le leader — une
     * couleur qu'on fait choisir et qui ne suit nulle part.
     */
    $project = Project::factory()->create([
        'book_cover' => BookCover::Terracotta,
        'collection_started_at' => '2026-01-15',
    ]);
    Narrator::factory()->create([
        'project_id' => $project->id,
        'first_name' => 'Marcelle',
        'is_primary' => true,
    ]);

    $book = Book::factory()->create(['project_id' => $project->id]);

    $html = app(RenderBookHtml::class)->handle($book);

    expect($html)->toContain('--cover-background:'.BookCover::Terracotta->background())
        ->and($html)->toContain('--cover-ink:'.BookCover::Terracotta->ink());
});

it('imprime le titre choisi sur la couverture du BAT', function (): void {
    $project = Project::factory()->create([
        'book_title' => BookTitle::Memories,
        'collection_started_at' => '2026-01-15',
    ]);
    Narrator::factory()->create([
        'project_id' => $project->id,
        'first_name' => 'Odette',
        'is_primary' => true,
    ]);

    $html = app(RenderBookHtml::class)->handle(
        Book::factory()->create(['project_id' => $project->id]),
    );

    // Élidé : « Les souvenirs de Odette » se lit mal, et c'est le même
    // `Names::of()` que l'écran a employé pour montrer le titre.
    expect($html)->toContain('Les souvenirs d’Odette');
});

it('laisse le prénom seul aux projets d’avant le choix', function (): void {
    $project = Project::factory()->create(['collection_started_at' => '2026-01-15']);
    Narrator::factory()->create([
        'project_id' => $project->id,
        'first_name' => 'Marcelle',
        'is_primary' => true,
    ]);

    $html = app(RenderBookHtml::class)->handle(
        Book::factory()->create(['project_id' => $project->id]),
    );

    expect($project->book_title)->toBe(BookTitle::FirstName)
        ->and($html)->toContain('<h1 class="cover-title">Marcelle</h1>');
});

it('laisse l’ivoire aux projets d’avant le choix', function (): void {
    // La valeur par défaut est exactement la teinte qu'avaient toutes les
    // couvertures avant cet écran : personne ne reçoit un autre livre parce
    // qu'on a ajouté une question.
    $project = Project::factory()->create(['collection_started_at' => '2026-01-15']);
    Narrator::factory()->create([
        'project_id' => $project->id,
        'first_name' => 'Marcelle',
        'is_primary' => true,
    ]);

    $html = app(RenderBookHtml::class)->handle(
        Book::factory()->create(['project_id' => $project->id]),
    );

    expect($project->book_cover)->toBe(BookCover::Ivory)
        ->and($html)->toContain('--cover-background:#faf7f2');
});

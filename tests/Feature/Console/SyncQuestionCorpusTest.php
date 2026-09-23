<?php

declare(strict_types=1);

use App\Enums\QuestionTone;
use App\Models\Question;
use App\Models\Story;

/*
 * `corpus:sync` : le seul chemin par lequel le corpus arrive en production.
 *
 * À blanc par défaut, parce qu'on la lance à la main en SSH et qu'on lit son
 * rapport avant de l'appliquer. Jamais de suppression : une question déjà
 * posée porte des histoires, et la retirer du fichier ne doit rien casser.
 */

/** Écrit un fichier de corpus temporaire et rend son chemin. */
function corpusFile(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'corpus').'.php';
    file_put_contents($path, '<?php return '.var_export($entries, true).';');

    return $path;
}

/** @return array<string, mixed> */
function corpusEntry(string $slug, array $overrides = []): array
{
    return array_merge([
        'slug' => $slug,
        'theme' => 'childhood',
        'difficulty' => 1,
        'order' => 10,
        'tone' => 'light',
        'conditions' => [],
        'sensitive' => [],
        'vous' => 'Quel est votre souvenir ?',
        'tu' => 'Quel est ton souvenir ?',
    ], $overrides);
}

beforeEach(function (): void {
    // Le TestCase sème le corpus de référence : on part d'une table vide pour
    // que chaque test dise lui-même ce qui existe.
    Story::query()->delete();
    Question::query()->delete();
});

it('n’écrit rien sans --apply, et dit ce qu’elle ferait', function (): void {
    $file = corpusFile([corpusEntry('nouvelle')]);

    $this->artisan('corpus:sync', ['--file' => $file])
        ->expectsOutputToContain('À blanc')
        ->expectsOutputToContain('1 ajoutée')
        ->assertSuccessful();

    expect(Question::query()->count())->toBe(0);
});

it('ajoute les questions nouvelles avec leurs étiquettes', function (): void {
    $file = corpusFile([corpusEntry('rencontre', [
        'tone' => 'tender', 'conditions' => ['partner'], 'sensitive' => ['bereavement', 'separation'],
    ])]);

    $this->artisan('corpus:sync', ['--file' => $file, '--apply' => true])->assertSuccessful();

    $question = Question::query()->where('slug', 'rencontre')->firstOrFail();

    expect($question->text)->toBe('Quel est votre souvenir ?')
        ->and($question->text_tu)->toBe('Quel est ton souvenir ?')
        ->and($question->tone)->toBe(QuestionTone::Tender)
        ->and($question->conditions)->toBe(['partner'])
        ->and($question->sensitive_topics)->toBe(['bereavement', 'separation'])
        ->and($question->is_active)->toBeTrue();
});

it('met à jour ce qui a changé et nomme les champs', function (): void {
    Question::factory()->create(['slug' => 'souvenir', 'text' => 'Ancien texte ?', 'order_hint' => 10, 'theme' => 'childhood', 'difficulty' => 1]);

    $file = corpusFile([corpusEntry('souvenir')]);

    $this->artisan('corpus:sync', ['--file' => $file, '--apply' => true])
        // Une seule attente par ligne : la sortie d'une ligne ne satisfait
        // qu'une attente.
        ->expectsOutputToContain('~ souvenir : vous, tu, tone')
        ->assertSuccessful();

    expect(Question::query()->where('slug', 'souvenir')->value('text'))->toBe('Quel est votre souvenir ?');
});

it('distingue un accord posé d’une reformulation', function (): void {
    Question::factory()->create(['slug' => 'naissance', 'text' => 'Où êtes-vous né·e ?', 'theme' => 'childhood', 'difficulty' => 1, 'order_hint' => 10]);

    $file = corpusFile([corpusEntry('naissance', ['vous' => 'Où êtes-vous né{|e} ?', 'tu' => 'Où es-tu né{|e} ?'])]);

    $this->artisan('corpus:sync', ['--file' => $file])
        ->expectsOutputToContain('vous (marqueurs, même texte)')
        ->assertSuccessful();
});

it('ne fait rien la seconde fois', function (): void {
    $file = corpusFile([corpusEntry('souvenir')]);

    $this->artisan('corpus:sync', ['--file' => $file, '--apply' => true])->assertSuccessful();

    $this->artisan('corpus:sync', ['--file' => $file])
        ->expectsOutputToContain('Rien à changer')
        ->assertSuccessful();
});

it('ne touche pas aux questions absentes du fichier', function (): void {
    // Les questions de démonstration et de bout en bout vivent en base de dev
    // sans être au corpus ; celles d'hier, en production, portent des histoires.
    $demo = Question::factory()->create(['slug' => 'demo-shared', 'is_active' => true]);

    $this->artisan('corpus:sync', ['--file' => corpusFile([corpusEntry('souvenir')]), '--apply' => true])
        ->expectsOutputToContain('1 hors corpus')
        ->assertSuccessful();

    expect($demo->refresh()->is_active)->toBeTrue();
});

it('retire une question seulement quand le fichier le dit', function (): void {
    Question::factory()->create(['slug' => 'souvenir', 'is_active' => true]);

    $file = corpusFile([corpusEntry('souvenir', ['active' => false])]);

    $this->artisan('corpus:sync', ['--file' => $file, '--apply' => true])->assertSuccessful();

    expect(Question::query()->where('slug', 'souvenir')->value('is_active'))->toBeFalse();
});

it('refuse d’appliquer un fichier invalide, et n’écrit rien', function (): void {
    $file = corpusFile([corpusEntry('souvenir', ['vous' => 'Où êtes-vous né·e ?'])]);

    $this->artisan('corpus:sync', ['--file' => $file, '--apply' => true])
        ->expectsOutputToContain('point médian')
        ->assertFailed();

    expect(Question::query()->count())->toBe(0);
});

it('lit par défaut le corpus du dépôt', function (): void {
    $this->artisan('corpus:sync', ['--apply' => true])->assertSuccessful();

    expect(Question::query()->count())->toBe(164)
        ->and(Question::query()->whereNull('tone')->count())->toBe(0);
});

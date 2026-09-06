<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\Stories\Pages\ViewStory;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Le texte d'une histoire se lit et se corrige **depuis la fiche de
 * l'histoire**.
 *
 * Trouvé en jouant le checkpoint du bloc 11, dont le point 2 demande
 * d'ouvrir une fiche, d'écouter, puis de « corriger un mot du texte ». La
 * fiche n'affichait pas une ligne de texte : il fallait la quitter, ouvrir la
 * liste globale des transcriptions et y retrouver la bonne ligne parmi celles
 * de toutes les familles — sans autre repère que la famille et la version.
 * Le geste réel du support est pourtant « on me signale une faute dans cette
 * histoire-là » (T-186).
 *
 * Deux règles ne bougent pas au passage : le mot à mot ne se corrige jamais,
 * et le journal garde la **taille** du changement, pas les deux textes.
 */
function agentSupport(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

it('montre le mot à mot et le texte courant sur la fiche', function (): void {
    $story = Story::factory()->transcribed()->create();
    Transcript::factory()->for($story)->create(['text' => 'On cuisait le pain le mardi.']);
    Transcript::factory()->for($story)->fluide()->create(['text' => 'On cuisait le pain le mardi matin.']);

    Livewire::actingAs(agentSupport())
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->assertSee('On cuisait le pain le mardi.')
        ->assertSee('On cuisait le pain le mardi matin.');
});

it('n’offre pas de correction quand l’histoire n’a que son mot à mot', function (): void {
    $story = Story::factory()->transcribed()->create();
    Transcript::factory()->for($story)->create();

    Livewire::actingAs(agentSupport())
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->assertActionHidden('edit_text');
});

it('corrige un mot, crée une version et inscrit « edited Transcript »', function (): void {
    $story = Story::factory()->transcribed()->create();
    Transcript::factory()->for($story)->create(['text' => 'On cuisait le pain le mardi.']);
    Transcript::factory()->for($story)->fluide()->create(['text' => 'On cuisait le pain le mardi.']);

    Livewire::actingAs(agentSupport())
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->assertActionVisible('edit_text')
        ->callAction('edit_text', ['text' => 'On cuisait le pain le vendredi.'])
        ->assertHasNoActionErrors();

    // La correction, pas la mise au propre dont elle dérive : les deux restent
    // `is_current`, et c'est la préférence explicite qui départage.
    $courant = $story->transcripts()->where('kind', 'edited')->current()->firstOrFail();

    expect($courant->text)->toBe('On cuisait le pain le vendredi.')
        ->and($courant->version)->toBe(2);

    // Le mot à mot est intact : c'est ce que quelqu'un a dit.
    expect($story->transcripts()->where('kind', 'verbatim')->value('text'))
        ->toBe('On cuisait le pain le mardi.');

    $trace = DB::table('audit_logs')->where('action', 'edited Transcript')->first();

    expect($trace)->not->toBeNull();

    $meta = json_decode((string) $trace->payload, true);

    expect($meta['characters_before'])->toBe(28)
        ->and($meta['characters_after'])->toBe(31)
        // Ni l'un ni l'autre des deux textes : une entrée d'audit ne se
        // modifie plus, et y recopier le récit en ferait un second endroit
        // où il vit, celui-là indélébile.
        ->and((string) $trace->payload)->not->toContain('vendredi');
});

it('refuse la correction à un compte en lecture seule', function (): void {
    $story = Story::factory()->transcribed()->create();
    Transcript::factory()->for($story)->create();
    Transcript::factory()->for($story)->fluide()->create(['text' => 'Le texte.']);

    $lecteur = User::factory()->create(['role' => UserRole::SupportReadonly]);

    Livewire::actingAs($lecteur)
        ->test(ViewStory::class, ['record' => $story->getKey()])
        ->assertActionDisabled('edit_text');

    expect(DB::table('audit_logs')->where('action', 'edited Transcript')->count())->toBe(0);
});

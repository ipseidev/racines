<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Un proche pose sa question (R-1, dossier v3.1).
 *
 * C'est le maillon H2 que l'écoute seule ne produit pas : écouter et mettre un
 * cœur est passif ; demander à son aïeule ce qu'on a toujours voulu savoir ne
 * l'est pas. Et c'est du contenu qu'un corpus éditorial ne peut pas fabriquer,
 * faute de connaître la famille.
 */
function membre(Project $project, bool $peutDemander): array
{
    // `ProposeStory` refuse un projet sans narrateur principal : une question
    // sans personne à qui la poser n'en est pas une.
    Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Odette',
    ]);

    $member = FamilyMember::factory()->create([
        'project_id' => $project->id,
        'display_name' => 'Marie',
        'can_ask' => $peutDemander,
    ]);

    return [$member, app(TokenService::class)
        ->issue(TokenType::ListenProject, $member, ['listen', 'react'])->plain];
}

it('crée une histoire proposée, signée du proche qui l’a posée', function (): void {
    $project = Project::factory()->create();
    [$member, $token] = membre($project, peutDemander: true);

    $this->post("/l/{$token}/questions", [
        'text' => 'Comment vous êtes-vous rencontrés, mamie ?',
    ])->assertSessionHasNoErrors();

    $story = Story::query()->where('project_id', $project->id)->sole();

    expect($story->custom_question_text)->toBe('Comment vous êtes-vous rencontrés, mamie ?')
        ->and($story->state)->toBeInstanceOf(Proposed::class)
        // La colonne d'auteur, qui manquait : la frise pouvait afficher
        // « Votre question » et rien d'autre, faute de savoir qui demandait.
        ->and($story->proposed_by_family_member_id)->toBe($member->id)
        ->and($story->question_id)->toBeNull();
});

it('refuse la question d’un proche qui n’a pas ce droit', function (): void {
    $project = Project::factory()->create();
    [, $token] = membre($project, peutDemander: false);

    /*
     * Un jeton d'écoute valide ne suffit pas. Le droit est explicite et donné
     * personne par personne : on invite un cousin en lecture seule à côté
     * d'une fille qui pose des questions.
     */
    $this->post("/l/{$token}/questions", ['text' => 'Une question de trop ?'])
        ->assertForbidden();

    expect(Story::query()->where('project_id', $project->id)->count())->toBe(0);
});

it('refuse la question d’un proche retiré du cercle', function (): void {
    $project = Project::factory()->create();
    [$member, $token] = membre($project, peutDemander: true);

    $member->forceFill(['removed_at' => now()])->save();

    $this->post("/l/{$token}/questions", ['text' => 'Une question après le retrait ?'])
        ->assertForbidden();
});

/*
 * Derrière ce qui attend déjà, jamais devant.
 *
 * L'Initiateur·rice place les siennes au rang 0 — c'est son projet, et son
 * ordre délibéré gagne (T-63). Proposer une question n'est pas doubler la file.
 */
it('met la question derrière celles qui attendent déjà', function (): void {
    $project = Project::factory()->create();
    [, $token] = membre($project, peutDemander: true);

    Story::factory()->create([
        'project_id' => $project->id,
        'question_id' => null,
        'custom_question_text' => 'La question de l’Initiateur·rice',
        'sequence' => 1,
        'queue_order' => 0,
    ]);

    $this->post("/l/{$token}/questions", ['text' => 'Et la mienne, ensuite ?']);

    $posee = Story::query()
        ->where('custom_question_text', 'Et la mienne, ensuite ?')
        ->sole();

    expect($posee->queue_order)->toBeGreaterThan(0);
});

it('joint les photos à la question, et le narrateur les voit avec elle', function (): void {
    Storage::fake('media');

    $project = Project::factory()->create();
    [, $token] = membre($project, peutDemander: true);

    $this->post("/l/{$token}/questions", [
        'text' => 'Racontez-nous cette maison, s’il vous plaît.',
        'photos' => [UploadedFile::fake()->image('maison.jpg', 800, 600)],
    ])->assertSessionHasNoErrors();

    $story = Story::query()->where('project_id', $project->id)->sole();
    $photo = $story->getMedia(Story::PHOTOS)->sole();

    /*
     * `is_prompt` est tout l'intérêt : l'image s'affiche **avec** la question,
     * où elle appelle le récit, au lieu d'être collée sur une réponse déjà
     * faite où elle ne serait qu'une décoration.
     */
    expect($photo->getCustomProperty('is_prompt'))->toBeTrue();
});

it('refuse un texte trop court pour être une question', function (): void {
    $project = Project::factory()->create();
    [, $token] = membre($project, peutDemander: true);

    $this->post("/l/{$token}/questions", ['text' => 'Hein ?'])
        ->assertSessionHasErrors('text');
});

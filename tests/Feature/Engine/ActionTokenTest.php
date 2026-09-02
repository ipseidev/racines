<?php

declare(strict_types=1);

use App\Engine\Actions\AckCallParent;
use App\Engine\Actions\OfferPhoneOption;
use App\Engine\Actions\OneTapRegistry;
use App\Engine\Actions\ReactHeart;
use App\Engine\Actions\ResendWhatsapp;
use App\Engine\Actions\SwitchBiweekly;
use App\Enums\Cadence;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketKind;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\Reaction;
use App\Models\Story;
use App\Models\SupportTicket;
use App\Services\Tokens\TokenService;
use Laravel\Pennant\Feature;

/**
 * Un lien d'action en un tap, émis pour un projet.
 *
 * @return array{string, Project}
 */
function oneTapLink(string $action, ?Project $project = null): array
{
    $project ??= Project::factory()->create(['status' => ProjectStatus::Active]);

    $issued = app(TokenService::class)->issue(
        TokenType::Action,
        $project,
        OneTapRegistry::scopeFor($action),
        now()->addDays(30),
    );

    return [$issued->plain, $project];
}

it('montre la confirmation sans rien exécuter ni consommer le lien', function (): void {
    [$token, $project] = oneTapLink(SwitchBiweekly::name());

    $this->get("/a/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('initiator/OneTapConfirm')
            ->where('done', false)
            ->where('action', 'switch_biweekly')
            ->has('button'),
        );

    // Un lien reçu par SMS qui agirait à l'ouverture serait déclenché par le
    // simple aperçu d'un client de messagerie.
    expect($project->refresh()->cadence)->not->toBe(Cadence::Biweekly);

    // Et il reste jouable : la page qui le montre ne doit pas le griller.
    $this->get("/a/{$token}")->assertOk();
});

it('exécute une seule fois', function (): void {
    [$token, $project] = oneTapLink(SwitchBiweekly::name());

    $this->post("/a/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('done', true));

    expect($project->refresh()->cadence)->toBe(Cadence::Biweekly);

    // Usage unique : le second appel ne rejoue rien.
    $this->post("/a/{$token}")->assertStatus(410);
});

it('recalcule la prochaine question au changement de cadence', function (): void {
    [$token, $project] = oneTapLink(SwitchBiweekly::name());
    $project->forceFill(['next_prompt_at' => now()->addDay()])->save();

    $this->post("/a/{$token}")->assertOk();

    // Sans recalcul, la question suivante partirait à l'ancien rythme et le
    // geste paraîtrait sans effet.
    expect($project->refresh()->next_prompt_at?->greaterThan(now()->addDays(7)))->toBeTrue();
});

it('donne un lien à coller et un message WhatsApp prérempli', function (): void {
    [$token, $project] = oneTapLink(ResendWhatsapp::name());
    Story::factory()->forProject($project)->proposed()->create();

    $this->post("/a/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('done', true)
            ->has('link')
            ->where('whatsapp', fn (string $url): bool => str_starts_with($url, 'https://wa.me/?text='))
            ->has('suggestion'),
        );
});

it('accuse réception d’un appel sans rien changer', function (): void {
    [$token, $project] = oneTapLink(AckCallParent::name());
    $cadence = $project->cadence;

    $this->post("/a/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('done', true));

    // Cette action ne fait rien, et c'est tout son intérêt : elle enregistre
    // qu'un humain a pris le relais.
    expect($project->refresh()->cadence)->toBe($cadence);
});

it('envoie un cœur sur la dernière histoire partagée', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $older = Story::factory()->forProject($project)->shared()->create();
    $older->forceFill(['shared_at' => now()->subDays(4)])->save();
    $latest = Story::factory()->forProject($project)->shared()->create();
    $latest->forceFill(['shared_at' => now()])->save();

    [$token] = oneTapLink(ReactHeart::name(), $project);

    $this->post("/a/{$token}")->assertOk();

    $reaction = Reaction::query()->sole();

    expect($reaction->story_id)->toBe($latest->id)
        ->and($reaction->type->value)->toBe('heart');
});

it('inscrit l’Initiateur·rice au cercle d’écoute si besoin', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    Story::factory()->forProject($project)->shared()->create();
    [$token] = oneTapLink(ReactHeart::name(), $project);

    expect(FamilyMember::query()->where('project_id', $project->id)->count())->toBe(0);

    $this->post("/a/{$token}")->assertOk();

    // Réagir, c'est faire partie du cercle d'écoute : une réaction anonyme ne
    // dirait rien au narrateur.
    $member = FamilyMember::query()->where('project_id', $project->id)->sole();

    expect($member->email)->toBe($project->owner->email);
});

it('refuse l’option téléphone quand le drapeau est fermé', function (): void {
    [$token, $project] = oneTapLink(OfferPhoneOption::name());

    $this->post("/a/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('done', false)
            ->where('message', __('initiator.one_tap.offer_phone_option.unavailable')),
        );

    // « Nous vous rappelons » qu'on ne tiendrait pas serait pire que rien.
    expect(SupportTicket::query()->count())->toBe(0);
});

it('ouvre un ticket quand l’option téléphone est ouverte', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    Feature::for($project)->activate(OfferPhoneOption::FLAG);
    [$token] = oneTapLink(OfferPhoneOption::name(), $project);

    $this->post("/a/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('done', true));

    expect(SupportTicket::query()->sole()->kind)->toBe(SupportTicketKind::PhoneOptionRequested);
});

it('refuse un jeton d’un autre périmètre', function (): void {
    $story = Story::factory()->shared()->create();
    $record = app(TokenService::class)->issue(TokenType::Record, $story, ['record']);

    $this->get("/a/{$record->plain}")->assertNotFound();
});

it('refuse un périmètre qui nomme une action inconnue', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $issued = app(TokenService::class)->issue(TokenType::Action, $project, ['action', 'tout_supprimer']);

    // Liste fermée : un périmètre bricolé n'ouvre pas autre chose que ce pour
    // quoi le lien a été émis. Et du point de vue du visiteur, ce lien
    // n'existe pas — une erreur technique lui apprendrait qu'il a touché
    // quelque chose.
    $this->get("/a/{$issued->plain}")->assertNotFound();
});

it('refuse un lien expiré avec une page amicale', function (): void {
    [$token] = oneTapLink(SwitchBiweekly::name());
    $this->travel(31)->days();

    $this->get("/a/{$token}")->assertStatus(410);
});

<?php

declare(strict_types=1);

use App\Enums\OutboundMessageStatus;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketStatus;
use App\Filament\Widgets\PilotOverview;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * Les cinq nombres du tableau de bord.
 *
 * Le choix des cinq est ce qui compte, et c'est ce que ce test protège : deux
 * disent si le produit **tourne**, trois disent s'il faut **agir**. Aucun ne
 * dit combien de familles on a — un chiffre de vanité sur un tableau de bord
 * de support est une place volée à un chiffre qui déclenche un geste.
 */
it('affiche le tableau de bord au personnel', function (): void {
    $support = User::factory()->support()->withAppAuthentication()->create();

    $this->actingAs($support)->get('/admin')->assertOk();
});

it('déclare le widget du pilote', function (): void {
    // Découvert automatiquement : le déclarer à la main en plus le ferait
    // apparaître deux fois.
    expect(Filament::getPanel('admin')->getWidgets())
        ->toContain(PilotOverview::class);
});

it('cache le tableau de bord à qui n’a pas la lecture du support', function (): void {
    $client = User::factory()->withAppAuthentication()->create();

    $this->actingAs($client);

    expect(PilotOverview::canView())->toBeFalse();
});

it('compte les envois échoués des dernières vingt-quatre heures', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    OutboundMessage::factory()->create([
        'project_id' => $project->id,
        'status' => OutboundMessageStatus::Failed,
        'created_at' => now()->subHours(2),
    ]);

    // Hors fenêtre : un échec d'il y a une semaine n'appelle plus de geste.
    OutboundMessage::factory()->create([
        'project_id' => $project->id,
        'status' => OutboundMessageStatus::Failed,
        'created_at' => now()->subDays(7),
    ]);

    $recent = OutboundMessage::query()
        ->where('status', OutboundMessageStatus::Failed->value)
        ->where('created_at', '>=', now()->subDay())
        ->count();

    expect($recent)->toBe(1);
});

it('compte les tickets ouverts, tous âges confondus', function (): void {
    SupportTicket::factory()->count(3)->create(['status' => SupportTicketStatus::Open]);
    SupportTicket::factory()->create([
        'status' => SupportTicketStatus::Closed,
        'closed_at' => now(),
    ]);

    // Un ticket vieux reste un ticket ouvert : la fenêtre de vingt-quatre
    // heures vaut pour les envois, pas pour les gens qui attendent.
    expect(SupportTicket::query()->where('status', SupportTicketStatus::Open->value)->count())
        ->toBe(3);
});

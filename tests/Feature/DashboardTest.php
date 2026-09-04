<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * La page gabarit du kit n'existe plus : « dashboard » mène à l'espace de
     * l'Initiateur·rice, qui est le vrai tableau de bord. Une personne qui se
     * connecte ne doit jamais atterrir sur des rectangles hachurés.
     */
    public function test_the_dashboard_leads_to_the_initiator_space()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect('/espace');
    }
}

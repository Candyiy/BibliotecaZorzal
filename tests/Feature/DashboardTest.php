<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_la_ruta_del_antiguo_panel_redirige_a_libros(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertRedirect(route('libros.index'));
    }

    public function test_el_panel_general_ya_no_aparece_en_la_navegacion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertDontSee('Panel general');
    }
}

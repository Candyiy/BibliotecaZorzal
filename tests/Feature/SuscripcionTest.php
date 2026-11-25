<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuscripcionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_historial_de_suscripciones_requiere_autenticacion(): void
    {
        $this->get(route('suscripciones.index'))
            ->assertRedirect(route('login'));
    }

    public function test_el_cliente_ve_solo_sus_suscripciones_con_plan_y_tipo(): void
    {
        $cliente = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->crearSuscripcion($cliente, $mensual);
        $this->crearSuscripcion($otro, $anual);

        $this->actingAs($cliente)
            ->get(route('suscripciones.index'))
            ->assertOk()
            ->assertSee('Mis suscripciones')
            ->assertSee(route('suscripciones.index'), false)
            ->assertSee('Plan Mensual')
            ->assertSee('Mensual')
            ->assertDontSee('Plan Anual')
            ->assertDontSee('Anual');
    }

    public function test_muestra_un_estado_vacio_cuando_no_hay_suscripciones(): void
    {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)
            ->get(route('suscripciones.index'))
            ->assertOk()
            ->assertSee('Todavía no tienes suscripciones.')
            ->assertSee('Elige un plan y aparecerá aquí.');
    }

    public function test_el_historial_de_suscripciones_se_puede_buscar(): void
    {
        $cliente = User::factory()->cliente()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->crearSuscripcion($cliente, $mensual);
        $this->crearSuscripcion($cliente, $anual);

        $this->actingAs($cliente)
            ->get(route('suscripciones.index', ['q' => 'anual']))
            ->assertOk()
            ->assertSee('Plan Anual')
            ->assertDontSee('Plan Mensual');
    }

    private function crearSuscripcion(User $usuario, Plan $plan): void
    {
        Suscripcion::create([
            'user_id' => $usuario->id,
            'plan_id' => $plan->id,
            'f_inicio' => today()->subMonth()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);
    }
}

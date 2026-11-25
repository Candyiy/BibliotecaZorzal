<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Promocion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_presentacion_muestra_los_planes_con_sus_precios(): void
    {
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        Promocion::create([
            'motivo' => 'Bienvenida',
            'cuanto' => '20.00',
            'f_inicio' => today()->subDay()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ])->planes()->sync([$plan->id]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Un catálogo para encontrar títulos')
            ->assertSee('El estante')
            ->assertSee('Tarifas de la biblioteca')
            ->assertSee('Mensual')
            ->assertSee('9,99')
            ->assertSee('7,99')
            ->assertSee('Bienvenida · − 20,00 %')
            ->assertSee('Suscribirme');
    }

    public function test_la_presentacion_no_muestra_planes_inactivos(): void
    {
        Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        Plan::create([
            'tipo' => 'anual',
            'precio' => '99.00',
            'estado' => Plan::ESTADO_INACTIVA,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Mensual')
            ->assertDontSee('99,00');
    }

    public function test_la_presentacion_avisa_cuando_no_hay_planes(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Todavía no hay planes publicados.');
    }
}

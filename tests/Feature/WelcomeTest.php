<?php

namespace Tests\Feature;

use App\Models\Libro;
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
            ->assertSee('con alma Zorzal')
            ->assertSee('El estante')
            ->assertSee('Tarifas de la biblioteca')
            ->assertSee('Mensual')
            ->assertSee('9,99')
            ->assertSee('7,99')
            ->assertSee('Bienvenida · − 20,00 %')
            ->assertSee('Suscribirme');
    }

    public function test_la_presentacion_muestra_prestamo_en_los_planes_por_libro(): void
    {
        $libro = Libro::create([
            'titulo' => 'Libro por días',
            'descripcion' => 'Descripción del libro.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        Plan::create([
            'tipo' => 'dias',
            'duracion_dias' => 10,
            'precio' => '4.99',
            'modalidad' => Plan::MODALIDAD_LIBRO,
            'libro_id' => $libro->id,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Préstamo')
            ->assertSee('10 días')
            ->assertDontSee('Suscribirme');
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

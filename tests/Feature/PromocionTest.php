<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Promocion;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromocionTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_crear_editar_y_eliminar_una_promocion(): void
    {
        $administrador = User::factory()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($administrador)
            ->get(route('promociones.index'))
            ->assertOk()
            ->assertSee('command="show-modal"', false)
            ->assertSee('commandfor="crear-promocion"', false);

        $this->actingAs($administrador)
            ->post(route('promociones.store'), [
                '_modal' => 'crear',
                'motivo' => 'Bienvenida',
                'cuanto' => '20.00',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->addMonth()->toDateString(),
                'estado' => 'activa',
                'planes' => [$mensual->id],
            ])
            ->assertRedirect(route('promociones.index'))
            ->assertSessionHasNoErrors();

        $promocion = Promocion::firstOrFail();

        $this->assertSame('Bienvenida', $promocion->motivo);
        $this->assertSame('20.00', $promocion->cuanto);
        $this->assertTrue($promocion->planes->contains($mensual));
        $this->assertDatabaseHas('promocion_plan', [
            'promocion_id' => $promocion->id,
            'plan_id' => $mensual->id,
        ]);

        $this->actingAs($administrador)
            ->get(route('promociones.index'))
            ->assertOk()
            ->assertSee('commandfor="editar-'.$promocion->id.'"', false)
            ->assertSee('<dialog id="editar-'.$promocion->id.'"', false);

        $this->actingAs($administrador)
            ->put(route('promociones.update', $promocion), [
                '_modal' => 'editar-'.$promocion->id,
                'motivo' => 'Oferta de temporada',
                'cuanto' => '10.00',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->addMonth()->toDateString(),
                'estado' => 'inactiva',
                'planes' => [$anual->id],
            ])
            ->assertRedirect(route('promociones.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Oferta de temporada', $promocion->fresh()->motivo);
        $this->assertSame('10.00', $promocion->fresh()->cuanto);
        $this->assertTrue($promocion->fresh()->planes->contains($anual));
        $this->assertFalse($promocion->fresh()->planes->contains($mensual));

        $this->actingAs($administrador)
            ->delete(route('promociones.destroy', $promocion))
            ->assertRedirect(route('promociones.index'));

        $this->assertDatabaseMissing('promociones', ['id' => $promocion->id]);
        $this->assertDatabaseCount('promocion_plan', 0);
    }

    public function test_valida_los_datos_y_reabre_el_modal(): void
    {
        $administrador = User::factory()->create();

        $respuesta = $this->actingAs($administrador)
            ->from(route('promociones.index'))
            ->followingRedirects()
            ->post(route('promociones.store'), [
                '_modal' => 'crear',
                'motivo' => '',
                'cuanto' => '0',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->subDay()->toDateString(),
                'estado' => 'activa',
                'planes' => [],
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false)
            ->assertSee('El campo motivo es obligatorio.', false)
            ->assertSee('El descuento debe ser mayor que 0 %.', false)
            ->assertSee('La fecha de finalización no puede ser anterior a la de inicio.', false)
            ->assertSee('Selecciona al menos un plan.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-promocion"\s+open/',
            $respuesta->getContent()
        );

        $this->assertDatabaseCount('promociones', 0);
    }

    public function test_el_descuento_no_puede_superar_el_cien_por_ciento(): void
    {
        $administrador = User::factory()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $this->actingAs($administrador)
            ->from(route('promociones.index'))
            ->followingRedirects()
            ->post(route('promociones.store'), [
                '_modal' => 'crear',
                'motivo' => 'Descuento imposible',
                'cuanto' => '101',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->addMonth()->toDateString(),
                'estado' => 'activa',
                'planes' => [$mensual->id],
            ])
            ->assertOk()
            ->assertSee('El descuento no puede superar el 100 %.', false);

        $this->assertDatabaseCount('promociones', 0);
    }

    public function test_el_porcentaje_se_aplica_a_cualquier_precio_y_nunca_baja_de_cero(): void
    {
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $promocion = Promocion::create([
            'motivo' => 'Bienvenida',
            'cuanto' => '20.00',
            'f_inicio' => today()->subDay()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);
        $promocion->planes()->sync([$mensual->id, $anual->id]);

        $this->assertSame('20,00 %', $promocion->etiquetaDescuento());
        $this->assertSame(2.0, $promocion->montoDescontado($mensual->precio));
        $this->assertSame(7.99, $mensual->fresh()->precioFinal());
        $this->assertSame(19.8, $promocion->montoDescontado($anual->precio));
        $this->assertSame(79.2, $anual->fresh()->precioFinal());

        $total = Promocion::create([
            'motivo' => 'Regalo',
            'cuanto' => '100.00',
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->toDateString(),
            'estado' => 'activa',
        ]);

        $this->assertSame(0.0, $total->aplicarA($mensual->precio));
    }

    public function test_el_descuento_se_muestra_y_se_aplica_al_suscribirse(): void
    {
        $usuario = User::factory()->create();
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        Promocion::create([
            'motivo' => 'Bienvenida',
            'cuanto' => '20.00',
            'f_inicio' => today()->subDay()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ])->planes()->sync([$plan->id]);

        $this->assertSame(7.99, $plan->fresh()->precioFinal());

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('7,99')
            ->assertSee('9,99')
            ->assertSee('Bienvenida');

        $respuesta = $this->actingAs($usuario)
            ->post(route('planes.suscribir', $plan));

        $suscripcion = Suscripcion::firstOrFail();

        $respuesta->assertRedirect(route('planes.confirmacion', $suscripcion));

        $this->assertDatabaseHas('suscripciones', [
            'user_id' => $usuario->id,
            'plan_id' => $plan->id,
            'estado' => 'activa',
        ]);
    }

    public function test_una_promocion_inactiva_no_aplica_el_descuento(): void
    {
        $usuario = User::factory()->create();
        $plan = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);
        Promocion::create([
            'motivo' => 'Solo internos',
            'cuanto' => '30.00',
            'f_inicio' => today()->subDay()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'inactiva',
        ])->planes()->sync([$plan->id]);

        $this->assertSame(99.0, $plan->fresh()->precioFinal());

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('99,00')
            ->assertDontSee('69,00');
    }

    public function test_las_promociones_requieren_autenticacion(): void
    {
        $promocion = Promocion::create([
            'motivo' => 'Bienvenida',
            'cuanto' => '2.00',
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);

        $this->get(route('promociones.index'))->assertRedirect(route('login'));
        $this->delete(route('promociones.destroy', $promocion))->assertRedirect(route('login'));
    }

    public function test_el_alta_de_promocion_puede_abrirse_con_un_plan_preseleccionado(): void
    {
        $administrador = User::factory()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $respuesta = $this->actingAs($administrador)
            ->get(route('promociones.index', ['plan' => $mensual->id]))
            ->assertOk()
            ->assertSee('Crea un descuento para el plan Mensual.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-promocion"\s+open/',
            $respuesta->getContent()
        );

        $this->assertMatchesRegularExpression(
            '/<option value="'.$mensual->id.'" selected>/',
            $respuesta->getContent()
        );
    }
}

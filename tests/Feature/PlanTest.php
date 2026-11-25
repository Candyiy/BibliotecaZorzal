<?php

namespace Tests\Feature;

use App\Models\Libro;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_crear_editar_y_eliminar_un_plan(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Suscriptores')
            ->assertSee('command="show-modal"', false)
            ->assertSee('commandfor="crear-plan"', false);

        $this->actingAs($usuario)
            ->post(route('planes.store'), [
                '_modal' => 'crear',
                'tipo' => 'mensual',
                'precio' => '9.99',
            ])
            ->assertRedirect(route('planes.index'));

        $plan = Plan::firstOrFail();

        $this->assertSame('mensual', $plan->tipo);
        $this->assertSame('9.99', $plan->precio);

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('commandfor="editar-'.$plan->id.'"', false)
            ->assertSee('<dialog id="editar-'.$plan->id.'"', false)
            ->assertSee('commandfor="eliminar-plan-'.$plan->id.'"', false)
            ->assertSee(route('promociones.index', ['plan' => $plan->id]), false)
            ->assertSee('Duración')
            ->assertSee('1 mes')
            ->assertSee('por mes');

        $this->actingAs($usuario)
            ->put(route('planes.update', $plan), [
                '_modal' => 'editar-'.$plan->id,
                'tipo' => 'anual',
                'precio' => '99.00',
            ])
            ->assertRedirect(route('planes.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('anual', $plan->fresh()->tipo);
        $this->assertSame('99.00', $plan->fresh()->precio);

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('1 año')
            ->assertSee('por año');

        $this->actingAs($usuario)
            ->delete(route('planes.destroy', $plan))
            ->assertRedirect(route('planes.index'));

        $this->assertDatabaseMissing('planes', ['id' => $plan->id]);
    }

    public function test_los_errores_de_validacion_reabren_el_modal_correspondiente(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('planes.index'))
            ->followingRedirects()
            ->post(route('planes.store'), [
                '_modal' => 'crear',
                'tipo' => '',
                'precio' => '-1',
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false)
            ->assertSee('El campo tipo es obligatorio.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-plan"\s+open/',
            $respuesta->getContent()
        );

        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->from(route('planes.index'))
            ->followingRedirects()
            ->put(route('planes.update', $plan), [
                '_modal' => 'editar-'.$plan->id,
                'tipo' => '',
                'precio' => '9.99',
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="editar-'.$plan->id.'"\s+open/',
            $respuesta->getContent()
        );

        $this->assertSame('mensual', $plan->fresh()->tipo);
    }

    public function test_un_usuario_puede_suscribirse_a_un_plan_mensual(): void
    {
        $usuario = User::factory()->create();
        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->post(route('planes.suscribir', $plan))
            ->assertSessionHasNoErrors();

        $suscripcion = Suscripcion::firstOrFail();

        $respuesta->assertRedirect(route('planes.confirmacion', $suscripcion));

        $this->assertSame($usuario->id, $suscripcion->user_id);
        $this->assertSame($plan->id, $suscripcion->plan_id);
        $this->assertSame('activa', $suscripcion->estado);
        $this->assertSame(today()->toDateString(), $suscripcion->f_inicio->toDateString());
        $this->assertSame(today()->addMonth()->toDateString(), $suscripcion->f_finalizacion->toDateString());

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Tu suscripción activa')
            ->assertSee('Plan Mensual')
            ->assertSee('1 mes');
    }

    public function test_la_suscripcion_anual_dura_un_anio(): void
    {
        $usuario = User::factory()->create();
        $plan = Plan::create([
            'tipo' => 'anual',
            'precio' => '99.00',
        ]);

        $this->assertSame(12, $plan->duracionEnMeses());
        $this->assertSame('1 año', $plan->duracionEtiqueta());

        $respuesta = $this->actingAs($usuario)
            ->post(route('planes.suscribir', $plan));

        $suscripcion = Suscripcion::firstOrFail();

        $respuesta->assertRedirect(route('planes.confirmacion', $suscripcion));

        $this->assertSame(today()->toDateString(), $suscripcion->f_inicio->toDateString());
        $this->assertSame(today()->addYear()->toDateString(), $suscripcion->f_finalizacion->toDateString());

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Plan Anual')
            ->assertSee('1 año');
    }

    public function test_la_confirmacion_muestra_el_plan_y_el_tipo(): void
    {
        $cliente = User::factory()->cliente()->create();
        $plan = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($cliente)->post(route('planes.suscribir', $plan));

        $suscripcion = Suscripcion::firstOrFail();

        $this->actingAs($cliente)
            ->get(route('planes.confirmacion', $suscripcion))
            ->assertOk()
            ->assertSee('Suscripción confirmada')
            ->assertSee('Plan Anual')
            ->assertSee('Tipo')
            ->assertSee('Anual');
    }

    public function test_otro_usuario_no_puede_ver_la_confirmacion(): void
    {
        $ana = User::factory()->create();
        $luis = User::factory()->create();
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $this->actingAs($ana)->post(route('planes.suscribir', $plan));

        $suscripcion = Suscripcion::firstOrFail();

        $this->actingAs($luis)
            ->get(route('planes.confirmacion', $suscripcion))
            ->assertForbidden();
    }

    public function test_suscribirse_a_otro_plan_cancela_la_suscripcion_activa(): void
    {
        $usuario = User::factory()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($usuario)->post(route('planes.suscribir', $mensual));
        $this->actingAs($usuario)->post(route('planes.suscribir', $anual));

        $this->assertSame('cancelada', $mensual->suscripciones()->first()->estado);
        $this->assertSame('activa', $anual->suscripciones()->first()->estado);
        $this->assertSame(1, $usuario->suscripciones()->where('estado', 'activa')->count());
        $this->assertSame(
            today()->addYear()->toDateString(),
            $anual->suscripciones()->first()->f_finalizacion->toDateString()
        );
    }

    public function test_un_usuario_puede_quitar_su_plan_activo(): void
    {
        $usuario = User::factory()->create();
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $this->actingAs($usuario)->post(route('planes.suscribir', $plan));

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Quitar plan')
            ->assertSee('commandfor="quitar-plan"', false)
            ->assertSee('<dialog id="quitar-plan"', false);

        $this->actingAs($usuario)
            ->post(route('planes.cancelar', $plan))
            ->assertRedirect(route('planes.index'))
            ->assertSessionHas('status', 'Has quitado tu plan Mensual.');

        $this->assertSame('cancelada', $plan->suscripciones()->first()->estado);
        $this->assertSame(0, $usuario->suscripciones()->where('estado', 'activa')->count());

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertDontSee('Tu suscripción activa')
            ->assertDontSee('<dialog id="quitar-plan"', false);
    }

    public function test_quitar_plan_no_afecta_la_suscripcion_de_otros_usuarios(): void
    {
        $ana = User::factory()->create();
        $luis = User::factory()->create();
        $plan = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($ana)->post(route('planes.suscribir', $plan));
        $this->actingAs($luis)->post(route('planes.suscribir', $plan));

        $this->actingAs($ana)->post(route('planes.cancelar', $plan));

        $this->assertSame(1, $ana->suscripciones()->where('estado', 'cancelada')->count());
        $this->assertSame(1, $luis->suscripciones()->where('estado', 'activa')->count());
    }

    public function test_el_modulo_de_planes_requiere_autenticacion(): void
    {
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $this->get(route('planes.index'))->assertRedirect(route('login'));
        $this->post(route('planes.suscribir', $plan))->assertRedirect(route('login'));
        $this->post(route('planes.cancelar', $plan))->assertRedirect(route('login'));
    }

    public function test_crear_un_plan_inactiva_solo_el_anterior_del_mismo_tipo(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('planes.store'), ['tipo' => 'mensual', 'precio' => '9.99']);
        $mensualViejo = Plan::firstOrFail();

        $this->actingAs($usuario)->post(route('planes.store'), ['tipo' => 'anual', 'precio' => '99.00']);
        $anual = Plan::where('tipo', 'anual')->firstOrFail();

        $this->actingAs($usuario)->post(route('planes.store'), ['tipo' => 'mensual', 'precio' => '11.99']);
        $mensualNuevo = Plan::where('tipo', 'mensual')->latest('id')->firstOrFail();

        $this->assertSame(Plan::ESTADO_INACTIVA, $mensualViejo->fresh()->estado);
        $this->assertSame(Plan::ESTADO_ACTIVA, $anual->fresh()->estado);
        $this->assertSame(Plan::ESTADO_ACTIVA, $mensualNuevo->estado);
        $this->assertSame(1, Plan::where('tipo', 'mensual')->where('estado', Plan::ESTADO_ACTIVA)->count());
        $this->assertSame(1, Plan::where('tipo', 'anual')->where('estado', Plan::ESTADO_ACTIVA)->count());
    }

    public function test_el_cliente_no_ve_planes_inactivos_ni_la_columna_estado(): void
    {
        $cliente = User::factory()->cliente()->create();
        $inactivo = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $activo = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);
        $inactivo->update(['estado' => Plan::ESTADO_INACTIVA]);

        $this->actingAs($cliente)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertDontSee('Suscriptores')
            ->assertDontSee('<th scope="col">Estado</th>', false)
            ->assertDontSee('Inactiva')
            ->assertDontSee('No disponible')
            ->assertDontSee('9,99')
            ->assertDontSee(route('planes.suscribir', $inactivo), false)
            ->assertDontSee('commandfor="suscribir-'.$inactivo->id.'"', false)
            ->assertSee(route('planes.suscribir', $activo), false)
            ->assertSee('commandfor="suscribir-'.$activo->id.'"', false)
            ->assertSee('<dialog id="suscribir-'.$activo->id.'"', false)
            ->assertSee('Confirmar suscripción')
            ->assertSee($cliente->name)
            ->assertSee($cliente->email);
    }

    public function test_el_staff_si_ve_los_planes_inactivos_y_la_columna_estado(): void
    {
        $staff = User::factory()->admin()->create();
        $inactivo = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $inactivo->update(['estado' => Plan::ESTADO_INACTIVA]);

        $this->actingAs($staff)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('<th scope="col">Estado</th>', false)
            ->assertSee('Inactiva')
            ->assertSee('No disponible')
            ->assertSee('9,99');
    }

    public function test_no_se_puede_suscribir_a_un_plan_inactivo(): void
    {
        $cliente = User::factory()->cliente()->create();
        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
            'estado' => Plan::ESTADO_INACTIVA,
        ]);

        $this->actingAs($cliente)
            ->post(route('planes.suscribir', $plan))
            ->assertForbidden();

        $this->assertSame(0, Suscripcion::count());
    }

    public function test_se_puede_reactivar_un_plan_y_el_otro_del_mismo_tipo_se_inactiva(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('planes.store'), ['tipo' => 'mensual', 'precio' => '9.99']);
        $primero = Plan::where('tipo', 'mensual')->oldest('id')->firstOrFail();

        $this->actingAs($usuario)->post(route('planes.store'), ['tipo' => 'mensual', 'precio' => '11.99']);
        $segundo = Plan::where('tipo', 'mensual')->latest('id')->firstOrFail();

        $this->actingAs($usuario)
            ->put(route('planes.update', $primero), [
                'tipo' => 'mensual',
                'precio' => '9.99',
                'estado' => Plan::ESTADO_ACTIVA,
            ])
            ->assertRedirect(route('planes.index'));

        $this->assertSame(Plan::ESTADO_ACTIVA, $primero->fresh()->estado);
        $this->assertSame(Plan::ESTADO_INACTIVA, $segundo->fresh()->estado);
    }

    public function test_cambiar_el_tipo_de_un_plan_activo_deactiva_el_activo_del_nuevo_tipo(): void
    {
        $usuario = User::factory()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($usuario)
            ->put(route('planes.update', $mensual), [
                'tipo' => 'anual',
                'precio' => '89.00',
                'estado' => Plan::ESTADO_ACTIVA,
            ])
            ->assertRedirect(route('planes.index'));

        $this->assertSame(Plan::ESTADO_ACTIVA, $mensual->fresh()->estado);
        $this->assertSame(Plan::ESTADO_INACTIVA, $anual->fresh()->estado);
        $this->assertSame(0, Plan::where('tipo', 'mensual')->where('estado', Plan::ESTADO_ACTIVA)->count());
    }

    public function test_puede_crear_planes_por_libro_y_paquete_con_prueba_y_beneficios(): void
    {
        $usuario = User::factory()->create();
        $libroUno = Libro::create([
            'titulo' => 'Libro del plan',
            'descripcion' => 'Descripción del libro del plan.',
            'fecha_publicacion' => '2020-01-01',
        ]);
        $libroDos = Libro::create([
            'titulo' => 'Segundo libro del paquete',
            'descripcion' => 'Descripción del segundo libro.',
            'fecha_publicacion' => '2021-01-01',
        ]);

        $this->actingAs($usuario)
            ->post(route('planes.store'), [
                '_modal' => 'crear',
                'tipo' => 'mensual',
                'precio' => '9.99',
                'modalidad' => Plan::MODALIDAD_LIBRO,
                'libro_id' => $libroUno->id,
                'trial_dias' => 7,
                'beneficios' => "Lectura sin anuncios\nDescargas offline",
            ])
            ->assertRedirect(route('planes.index'));

        $planLibro = Plan::where('tipo', 'mensual')->firstOrFail();

        $this->assertSame(Plan::MODALIDAD_LIBRO, $planLibro->modalidad);
        $this->assertSame($libroUno->id, $planLibro->libro_id);
        $this->assertSame(1, $planLibro->cantidad_libros);
        $this->assertSame(7, $planLibro->trial_dias);
        $this->assertSame(['Lectura sin anuncios', 'Descargas offline'], $planLibro->beneficiosLista());
        $this->assertTrue($planLibro->tienePrueba());

        $this->actingAs($usuario)
            ->post(route('planes.store'), [
                '_modal' => 'crear',
                'tipo' => 'anual',
                'precio' => '99.00',
                'modalidad' => Plan::MODALIDAD_PAQUETE,
                'libros' => [$libroUno->id, $libroDos->id],
                'beneficios' => 'Acceso al paquete completo',
            ])
            ->assertRedirect(route('planes.index'));

        $planPaquete = Plan::where('tipo', 'anual')->firstOrFail();

        $this->assertSame(Plan::MODALIDAD_PAQUETE, $planPaquete->modalidad);
        $this->assertSame(2, $planPaquete->cantidad_libros);
        $this->assertSame(2, $planPaquete->libros()->count());
        $this->assertSame('2 libros', $planPaquete->coberturaEtiqueta());

        $this->actingAs($usuario)
            ->put(route('planes.update', $planPaquete), [
                '_modal' => 'editar-'.$planPaquete->id,
                'tipo' => 'anual',
                'precio' => '109.00',
                'estado' => Plan::ESTADO_ACTIVA,
            ])
            ->assertRedirect(route('planes.index'));

        $this->assertSame(Plan::MODALIDAD_PAQUETE, $planPaquete->fresh()->modalidad);
        $this->assertSame(2, $planPaquete->fresh()->libros()->count());
    }

    public function test_un_plan_con_prueba_crea_suscripcion_de_prueba_y_pago_cero(): void
    {
        $cliente = User::factory()->cliente()->create();
        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
            'trial_dias' => 7,
            'beneficios' => ['Acceso anticipado'],
        ]);

        $this->actingAs($cliente)->post(route('planes.suscribir', $plan));

        $suscripcion = Suscripcion::firstOrFail();

        $this->assertTrue($suscripcion->esPrueba());
        $this->assertSame(today()->addDays(7)->toDateString(), $suscripcion->f_finalizacion->toDateString());

        $pago = Pago::firstOrFail();

        $this->assertSame('0.00', $pago->monto);
        $this->assertSame(Pago::ESTADO_PRUEBA, $pago->estado);

        $this->actingAs($cliente)
            ->get(route('planes.confirmacion', $suscripcion))
            ->assertOk()
            ->assertSee('Período de prueba')
            ->assertSee('Acceso anticipado');
    }
}

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

    public function test_el_formulario_de_planes_esta_organizado_y_la_cobertura_es_condicional(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Duración y precio')
            ->assertSee('¿A qué libros da acceso?', false)
            ->assertSee('Detalles opcionales')
            ->assertSee('data-plan-modalidad', false)
            ->assertSee('data-plan-tipo', false)
            ->assertSee('data-cobertura="libro"', false)
            ->assertSee('data-cobertura="paquete"', false)
            ->assertSee('data-duracion="dias"', false)
            ->assertSee('Por días · elige el plazo')
            ->assertSee('name="duracion_dias"', false)
            ->assertSee('Solo «Un libro específico» se paga por días.')
            ->assertSee('Crear plan');
    }

    public function test_un_plan_por_libro_se_contrata_por_dias_y_el_resto_sigue_siendo_mensual_o_anual(): void
    {
        $staff = User::factory()->admin()->create();
        $cliente = User::factory()->cliente()->create();
        $libro = Libro::create([
            'titulo' => 'Libro por días',
            'descripcion' => 'Descripción del libro por días.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        $this->actingAs($staff)->post(route('planes.store'), [
            'tipo' => 'mensual',
            'precio' => '9.99',
        ]);

        $this->actingAs($staff)->post(route('planes.store'), [
            'tipo' => 'anual',
            'precio' => '99.00',
            'modalidad' => Plan::MODALIDAD_PAQUETE,
            'libros' => [$libro->id],
        ]);

        $this->actingAs($staff)->post(route('planes.store'), [
            'tipo' => 'dias',
            'duracion_dias' => 12,
            'precio' => '4.99',
            'modalidad' => Plan::MODALIDAD_LIBRO,
            'libro_id' => $libro->id,
        ]);

        $general = Plan::where('modalidad', Plan::MODALIDAD_GENERAL)->firstOrFail();
        $paquete = Plan::where('modalidad', Plan::MODALIDAD_PAQUETE)->firstOrFail();
        $porLibro = Plan::where('modalidad', Plan::MODALIDAD_LIBRO)->firstOrFail();

        $this->assertSame('mensual', $general->tipo);
        $this->assertSame('1 mes', $general->duracionEtiqueta());
        $this->assertSame('anual', $paquete->tipo);
        $this->assertSame('1 año', $paquete->duracionEtiqueta());
        $this->assertSame('dias', $porLibro->tipo);
        $this->assertSame(12, $porLibro->duracion_dias);
        $this->assertSame('Por días', $porLibro->tipoEtiqueta());
        $this->assertSame('12 días', $porLibro->duracionEtiqueta());
        $this->assertSame('por 12 días', $porLibro->periodoPrecioEtiqueta());

        $this->actingAs($cliente)->post(route('planes.suscribir', $porLibro))->assertRedirect();

        $suscripcion = $cliente->suscripciones()->firstOrFail();

        $this->assertSame(today()->toDateString(), $suscripcion->f_inicio->toDateString());
        $this->assertSame(today()->addDays(12)->toDateString(), $suscripcion->f_finalizacion->toDateString());

        $this->actingAs($cliente)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Plan por días')
            ->assertSee('Por días')
            ->assertSee('12 días')
            ->assertSee('por 12 días');
    }

    public function test_los_dias_de_un_plan_por_libro_estan_entre_1_y_365(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Libro con plazo inválido',
            'descripcion' => 'Descripción del libro.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        $this->actingAs($usuario)
            ->post(route('planes.store'), [
                'tipo' => 'dias',
                'duracion_dias' => 0,
                'precio' => '4.99',
                'modalidad' => Plan::MODALIDAD_LIBRO,
                'libro_id' => $libro->id,
            ])
            ->assertSessionHasErrors('duracion_dias');

        $this->actingAs($usuario)
            ->post(route('planes.store'), [
                'tipo' => 'dias',
                'duracion_dias' => 366,
                'precio' => '4.99',
                'modalidad' => Plan::MODALIDAD_LIBRO,
                'libro_id' => $libro->id,
            ])
            ->assertSessionHasErrors('duracion_dias');

        $this->assertSame(0, Plan::count());
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

    public function test_el_cliente_suscrito_ve_otros_planes_pero_no_puede_cambiar_ni_quitar_el_suyo(): void
    {
        $cliente = User::factory()->cliente()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($cliente)->post(route('planes.suscribir', $mensual))->assertRedirect();

        $this->actingAs($cliente)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('Anual')
            ->assertSee('Solo lectura')
            ->assertSee('Tu plan activo no se puede cambiar.')
            ->assertDontSee('Cambiar a este plan')
            ->assertDontSee('Quitar plan')
            ->assertDontSee('commandfor="quitar-plan"', false)
            ->assertDontSee('<dialog id="quitar-plan"', false)
            ->assertDontSee('commandfor="suscribir-'.$anual->id.'"', false)
            ->assertDontSee('<dialog id="suscribir-'.$anual->id.'"', false);

        $this->actingAs($cliente)
            ->post(route('planes.suscribir', $anual))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->post(route('planes.cancelar', $mensual))
            ->assertForbidden();

        $this->assertSame('activa', $mensual->suscripciones()->first()->estado);
        $this->assertSame(0, $anual->suscripciones()->count());
        $this->assertSame(1, $cliente->suscripciones()->where('estado', 'activa')->count());
    }

    public function test_el_staff_si_puede_cambiar_de_plan(): void
    {
        $staff = User::factory()->admin()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($staff)->post(route('planes.suscribir', $mensual));
        $this->actingAs($staff)->post(route('planes.suscribir', $anual));

        $this->assertSame('cancelada', $mensual->suscripciones()->first()->estado);
        $this->assertSame('activa', $anual->suscripciones()->first()->estado);
        $this->assertSame(1, $staff->suscripciones()->where('estado', 'activa')->count());
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

    public function test_dos_clientes_pueden_suscribirse_al_mismo_plan(): void
    {
        $ana = User::factory()->cliente()->create();
        $luis = User::factory()->cliente()->create();
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $this->actingAs($ana)->post(route('planes.suscribir', $plan))->assertRedirect();

        $this->actingAs($luis)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('commandfor="suscribir-'.$plan->id.'"', false)
            ->assertSee('<dialog id="suscribir-'.$plan->id.'"', false);

        $this->actingAs($luis)->post(route('planes.suscribir', $plan))->assertRedirect();

        $this->assertSame(1, $ana->suscripciones()->where('estado', 'activa')->count());
        $this->assertSame(1, $luis->suscripciones()->where('estado', 'activa')->count());
        $this->assertSame(2, $plan->suscripciones()->where('estado', 'activa')->count());
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

    public function test_planes_del_mismo_tipo_con_distinta_cobertura_pueden_elegirlos_clientes_distintos(): void
    {
        $staff = User::factory()->admin()->create();
        $ana = User::factory()->cliente()->create();
        $luis = User::factory()->cliente()->create();
        $libro = Libro::create([
            'titulo' => 'Libro del plan',
            'descripcion' => 'Descripción del libro del plan.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        $this->actingAs($staff)->post(route('planes.store'), [
            'tipo' => 'mensual',
            'precio' => '9.99',
        ]);

        $this->actingAs($staff)->post(route('planes.store'), [
            'tipo' => 'dias',
            'duracion_dias' => 15,
            'precio' => '4.99',
            'modalidad' => Plan::MODALIDAD_LIBRO,
            'libro_id' => $libro->id,
        ]);

        $general = Plan::where('modalidad', Plan::MODALIDAD_GENERAL)->firstOrFail();
        $porLibro = Plan::where('modalidad', Plan::MODALIDAD_LIBRO)->firstOrFail();

        $this->assertSame(Plan::ESTADO_ACTIVA, $general->estado);
        $this->assertSame(Plan::ESTADO_ACTIVA, $porLibro->estado);

        $this->actingAs($ana)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('commandfor="suscribir-'.$general->id.'"', false)
            ->assertSee('commandfor="suscribir-'.$porLibro->id.'"', false);

        $this->actingAs($ana)->post(route('planes.suscribir', $general))->assertRedirect();

        $this->actingAs($luis)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertSee('commandfor="suscribir-'.$general->id.'"', false)
            ->assertSee('commandfor="suscribir-'.$porLibro->id.'"', false);

        $this->actingAs($luis)->post(route('planes.suscribir', $porLibro))->assertRedirect();

        $this->assertSame($general->id, $ana->suscripciones()->where('estado', 'activa')->firstOrFail()->plan_id);
        $this->assertSame($porLibro->id, $luis->suscripciones()->where('estado', 'activa')->firstOrFail()->plan_id);
    }

    public function test_planes_por_libro_distinto_conviven_y_el_mismo_libro_inactiva_el_anterior(): void
    {
        $staff = User::factory()->admin()->create();
        $libroA = Libro::create([
            'titulo' => 'Libro alfa',
            'descripcion' => 'Descripción del libro alfa.',
            'fecha_publicacion' => '2020-01-01',
        ]);
        $libroB = Libro::create([
            'titulo' => 'Libro beta',
            'descripcion' => 'Descripción del libro beta.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        foreach ([$libroA->id, $libroB->id, $libroA->id] as $indice => $libroId) {
            $this->actingAs($staff)->post(route('planes.store'), [
                'tipo' => 'dias',
                'duracion_dias' => 15,
                'precio' => number_format(9.99 + $indice, 2, '.', ''),
                'modalidad' => Plan::MODALIDAD_LIBRO,
                'libro_id' => $libroId,
            ]);
        }

        $planes = Plan::where('modalidad', Plan::MODALIDAD_LIBRO)->orderBy('id')->get();

        $this->assertSame(Plan::ESTADO_INACTIVA, $planes[0]->estado);
        $this->assertSame(Plan::ESTADO_ACTIVA, $planes[1]->estado);
        $this->assertSame(Plan::ESTADO_ACTIVA, $planes[2]->estado);
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
                'tipo' => 'dias',
                'duracion_dias' => 15,
                'precio' => '9.99',
                'modalidad' => Plan::MODALIDAD_LIBRO,
                'libro_id' => $libroUno->id,
                'trial_dias' => 7,
                'beneficios' => "Lectura sin anuncios\nDescargas offline",
            ])
            ->assertRedirect(route('planes.index'));

        $planLibro = Plan::where('tipo', 'dias')->firstOrFail();

        $this->assertSame(Plan::MODALIDAD_LIBRO, $planLibro->modalidad);
        $this->assertSame($libroUno->id, $planLibro->libro_id);
        $this->assertSame(1, $planLibro->cantidad_libros);
        $this->assertSame(15, $planLibro->duracion_dias);
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

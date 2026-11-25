<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\Plan;
use App\Models\Prestamo;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaDatosBiblioteca;
use Tests\TestCase;

class PrestamoTest extends TestCase
{
    use CreaDatosBiblioteca;
    use RefreshDatabase;

    public function test_los_prestamos_requieren_autenticacion(): void
    {
        $this->get(route('prestamos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_el_cliente_sin_suscripcion_no_puede_solicitar_prestamos(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia();

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertForbidden();

        $this->assertDatabaseMissing('prestamos', ['user_id' => $cliente->id]);
    }

    public function test_el_cliente_con_suscripcion_puede_solicitar_un_prestamo(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);
        $libro = $this->crearLibroConLicencia();

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertRedirect(route('prestamos.index'))
            ->assertSessionHas('status');

        $prestamo = Prestamo::firstOrFail();

        $this->assertDatabaseHas('prestamos', [
            'id' => $prestamo->id,
            'user_id' => $cliente->id,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
            'cantidad' => 1,
            'estado' => Prestamo::ESTADO_ACTIVO,
        ]);
        $this->assertSame(today()->toDateString(), $prestamo->f_inicio->toDateString());
        $this->assertSame(today()->addDays(Prestamo::DIAS_PLAZO)->toDateString(), $prestamo->f_finalizacion->toDateString());
    }

    public function test_no_se_pueden_superar_tres_prestamos_activos(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);

        foreach (range(1, Prestamo::MAX_ACTIVOS) as $numero) {
            $this->crearPrestamoActivo($cliente, $this->crearLibroConLicencia('Libro '.$numero));
        }

        $this->assertSame(Prestamo::MAX_ACTIVOS, $cliente->prestamos()->activos()->count());

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $this->crearLibroConLicencia('Libro excedente')))
            ->assertForbidden();

        $this->assertSame(Prestamo::MAX_ACTIVOS, $cliente->prestamos()->activos()->count());
    }

    public function test_un_prestamo_vencido_bloquea_nuevas_solicitudes(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);
        $libro = $this->crearLibroConLicencia();

        $cliente->prestamos()->create([
            'cantidad' => 1,
            'f_inicio' => today()->subDays(10)->toDateString(),
            'f_finalizacion' => today()->subDay()->toDateString(),
            'estado' => Prestamo::ESTADO_ACTIVO,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
        ]);

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $this->crearLibroConLicencia('Otro libro')))
            ->assertForbidden();
    }

    public function test_la_devolucion_tardía_registra_la_penalizacion(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia();
        $prestamo = $this->crearPrestamoActivo($cliente, $libro, today()->subDays(5), today()->subDays(2));

        $this->actingAs($cliente)
            ->post(route('prestamos.devolver', $prestamo))
            ->assertRedirect(route('prestamos.index'))
            ->assertSessionHas('status', 'Devolución registrada. Penalización de 2 días.');

        $prestamo->refresh();

        $this->assertDatabaseHas('prestamos', [
            'id' => $prestamo->id,
            'estado' => Prestamo::ESTADO_DEVUELTO,
            'dias_penalizacion' => 2,
        ]);
        $this->assertSame(today()->toDateString(), $prestamo->f_devolucion->toDateString());
    }

    public function test_la_penalizacion_bloquea_nuevos_prestamos_hasta_cumplirse(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);
        $libro = $this->crearLibroConLicencia();

        $cliente->prestamos()->create([
            'cantidad' => 1,
            'f_inicio' => today()->subDays(10)->toDateString(),
            'f_finalizacion' => today()->subDays(8)->toDateString(),
            'f_devolucion' => today()->toDateString(),
            'dias_penalizacion' => 2,
            'estado' => Prestamo::ESTADO_DEVUELTO,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
        ]);

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $this->crearLibroConLicencia('Libro posterior')))
            ->assertForbidden();
    }

    public function test_el_cliente_solo_ve_sus_prestamos_y_el_staff_ve_todos(): void
    {
        $cliente = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $staff = User::factory()->admin()->create();

        $this->crearPrestamoActivo($cliente, $this->crearLibroConLicencia('Libro del cliente'));
        $this->crearPrestamoActivo($otro, $this->crearLibroConLicencia('Libro ajeno'));

        $this->actingAs($cliente)
            ->get(route('prestamos.index'))
            ->assertOk()
            ->assertSee('Libro del cliente')
            ->assertDontSee('Libro ajeno');

        $this->actingAs($staff)
            ->get(route('prestamos.index'))
            ->assertOk()
            ->assertSee('Libro del cliente')
            ->assertSee('Libro ajeno')
            ->assertSee('Usuario');
    }

    public function test_el_historial_de_prestamos_se_puede_buscar_y_filtrar(): void
    {
        $staff = User::factory()->admin()->create();
        $this->crearPrestamoActivo($staff, $this->crearLibroConLicencia('Don Quijote'));
        $this->crearPrestamoActivo($staff, $this->crearLibroConLicencia('La Celestina'));

        $this->actingAs($staff)
            ->get(route('prestamos.index', ['q' => 'Quijote']))
            ->assertOk()
            ->assertSee('Don Quijote')
            ->assertDontSee('La Celestina');

        $this->actingAs($staff)
            ->get(route('prestamos.index', ['estado' => Prestamo::ESTADO_DEVUELTO]))
            ->assertOk()
            ->assertDontSee('Don Quijote');
    }

    public function test_un_cliente_no_puede_devolver_el_prestamo_de_otro_usuario(): void
    {
        $cliente = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $prestamo = $this->crearPrestamoActivo($otro, $this->crearLibroConLicencia());

        $this->actingAs($cliente)
            ->post(route('prestamos.devolver', $prestamo))
            ->assertForbidden();
    }

    public function test_un_prestamo_puede_renovarse_una_sola_vez_y_avisa_al_usuario(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);
        $prestamo = $this->crearPrestamoActivo(
            $cliente,
            $this->crearLibroConLicencia('Libro renovable'),
            today()->subDays(2),
            today()->addDays(2),
        );

        $this->actingAs($cliente)
            ->post(route('prestamos.renovar', $prestamo))
            ->assertRedirect(route('prestamos.index'))
            ->assertSessionHas('status');

        $prestamo->refresh();

        $this->assertSame(1, $prestamo->renovaciones);
        $this->assertSame(today()->addDays(9)->toDateString(), $prestamo->f_finalizacion->toDateString());
        $this->assertSame(1, $cliente->notifications()->where('data->tipo', 'renovado')->count());

        $this->actingAs($cliente)
            ->post(route('prestamos.renovar', $prestamo))
            ->assertStatus(422);

        $this->assertSame(1, $prestamo->fresh()->renovaciones);
    }

    public function test_el_historial_ordena_cronologicamente_y_respeta_el_control_de_orden(): void
    {
        $staff = User::factory()->admin()->create();

        $this->crearPrestamoActivo(
            $staff,
            $this->crearLibroConLicencia('Libro antiguo'),
            today()->subDays(10),
            today()->addDays(2),
        );
        $this->crearPrestamoActivo(
            $staff,
            $this->crearLibroConLicencia('Libro reciente'),
            today()->subDay(),
            today()->addDays(6),
        );

        $this->actingAs($staff)
            ->get(route('prestamos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Libro antiguo', 'Libro reciente']);

        $this->actingAs($staff)
            ->get(route('prestamos.index', ['orden' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Libro reciente', 'Libro antiguo']);
    }

    public function test_el_libro_con_contador_se_presta_sin_suscripcion_y_vence_en_24_dias(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicenciaContada('Libro con contador', 2);

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertRedirect(route('prestamos.index'))
            ->assertSessionHas('status', 'Préstamo solicitado correctamente. Devuélvelo en 24 días.');

        $prestamo = Prestamo::firstOrFail();

        $this->assertSame(today()->addDays(Prestamo::DIAS_PLAZO_CON_CONTADOR)->toDateString(), $prestamo->f_finalizacion->toDateString());
        $this->assertSame(1, $libro->licencias()->firstOrFail()->cantidad_lecturas);
        $this->assertSame(0, $cliente->suscripciones()->count());
    }

    public function test_un_cliente_con_prestamo_puede_suscribirse_a_un_plan_y_seguir_leyendo_el_libro(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicenciaContada('Libro prestado y suscrito', 3);
        $libro->update(['archivo_pdf' => 'libros/prestado-suscrito.pdf']);
        Storage::disk('local')->put($libro->archivo_pdf, 'contenido del pdf');

        $plan = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertRedirect(route('prestamos.index'));

        $this->actingAs($cliente)
            ->get(route('prestamos.index'))
            ->assertOk()
            ->assertSee('Leer '.$libro->titulo, false);

        $this->actingAs($cliente)
            ->post(route('planes.suscribir', $plan))
            ->assertRedirect();

        $this->assertSame(1, $cliente->suscripciones()->where('estado', 'activa')->count());
        $this->assertSame(1, $cliente->prestamos()->activos()->count());

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertOk();

        $this->assertSame(2, $libro->licencias()->firstOrFail()->cantidad_lecturas);
    }

    public function test_un_cliente_suscrito_no_cambia_de_plan_pero_si_puede_prestarse_un_libro_con_contador(): void
    {
        $cliente = User::factory()->cliente()->create();
        $planActual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $otroPlan = Plan::create(['tipo' => 'anual', 'precio' => '99.00']);
        $this->activarSuscripcion($cliente, $planActual);

        $libro = $this->crearLibroConLicenciaContada('Libro solo para préstamo', 2);
        $libro->update(['archivo_pdf' => 'libros/solo-prestamo.pdf']);
        Storage::disk('local')->put($libro->archivo_pdf, 'contenido del pdf');

        $this->actingAs($cliente)
            ->post(route('planes.suscribir', $otroPlan))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->post(route('planes.cancelar', $planActual))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertRedirect(route('prestamos.index'));

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertOk();

        $this->assertSame(1, $cliente->suscripciones()->where('estado', 'activa')->count());
        $this->assertSame($planActual->id, $cliente->suscripcionActiva()->firstOrFail()->plan_id);
        $this->assertSame(1, $libro->licencias()->firstOrFail()->cantidad_lecturas);
    }

    public function test_el_prestamo_usa_los_dias_y_precio_configurados_y_registra_el_pago(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicenciaContada('Libro con tarifa', 2, 10, '12.50');
        $libro->update(['archivo_pdf' => 'libros/tarifa.pdf']);

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Préstamo 12,50 · 10 días')
            ->assertSee('Pagas 12,50 y lo devuelves en 10 días.');

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertRedirect(route('prestamos.index'))
            ->assertSessionHas('status', 'Préstamo solicitado correctamente. Devuélvelo en 10 días.');

        $prestamo = Prestamo::firstOrFail();
        $pago = Pago::firstOrFail();

        $this->assertSame(today()->addDays(10)->toDateString(), $prestamo->f_finalizacion->toDateString());
        $this->assertSame('12.50', (string) $pago->monto);
        $this->assertSame($prestamo->id, $pago->prestamo_id);
        $this->assertSame($cliente->id, $pago->user_id);
        $this->assertNull($pago->suscripcion_id);
        $this->assertSame(Pago::ESTADO_PAGADO, $pago->estado);

        $this->actingAs($cliente)
            ->get(route('prestamos.index'))
            ->assertOk()
            ->assertSee('Préstamo de 10 días · sin renovación')
            ->assertSee('Pagado: 12,50');

        $this->actingAs($cliente)
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('Préstamo: Libro con tarifa');
    }

    public function test_un_libro_prestado_no_esta_disponible_para_otros_clientes_hasta_la_devolucion(): void
    {
        $ana = User::factory()->cliente()->create();
        $luis = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicenciaContada('Libro compartido', 2);

        $this->actingAs($ana)->post(route('prestamos.store', $libro))->assertRedirect();

        $this->actingAs($luis)
            ->post(route('prestamos.store', $libro))
            ->assertForbidden();

        $this->actingAs($luis)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Prestado a otro cliente');

        $this->assertSame(1, Prestamo::where('licencia_id', $libro->licencias()->firstOrFail()->id)->count());

        $this->actingAs($ana)->post(route('prestamos.devolver', Prestamo::firstOrFail()))->assertRedirect();

        $this->actingAs($luis)
            ->post(route('prestamos.store', $libro))
            ->assertRedirect();

        $this->assertSame(2, Prestamo::count());
    }

    public function test_al_agotarse_el_contador_el_libro_no_esta_disponible_ni_se_puede_leer(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicenciaContada('Libro agotado', 1);

        $this->actingAs($cliente)->post(route('prestamos.store', $libro))->assertRedirect();
        $this->actingAs($cliente)->post(route('prestamos.devolver', Prestamo::firstOrFail()))->assertRedirect();

        $this->assertSame(0, $libro->licencias()->firstOrFail()->cantidad_lecturas);

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Sin lecturas')
            ->assertSee('No disponible');

        $this->actingAs($cliente)
            ->post(route('prestamos.store', $libro))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertForbidden();
    }

    public function test_los_prestamos_con_contador_no_se_pueden_renovar_y_muestran_el_plazo(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicenciaContada('Libro sin renovación', 3);

        $this->actingAs($cliente)->post(route('prestamos.store', $libro))->assertRedirect();
        $prestamo = Prestamo::firstOrFail();

        $this->actingAs($cliente)
            ->post(route('prestamos.renovar', $prestamo))
            ->assertStatus(422);

        $this->assertSame(0, $prestamo->fresh()->renovaciones);

        $this->actingAs($cliente)
            ->get(route('prestamos.index'))
            ->assertOk()
            ->assertSee('Préstamo de 24 días · sin renovación');
    }

    private function crearPrestamoActivo(
        User $usuario,
        $libro,
        ?CarbonInterface $inicio = null,
        ?CarbonInterface $fin = null,
    ): Prestamo {
        $inicio ??= today();
        $fin ??= today()->addDays(Prestamo::DIAS_PLAZO);

        return $usuario->prestamos()->create([
            'cantidad' => 1,
            'f_inicio' => $inicio->toDateString(),
            'f_finalizacion' => $fin->toDateString(),
            'estado' => Prestamo::ESTADO_ACTIVO,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
        ]);
    }
}

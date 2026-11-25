<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\Plan;
use App\Models\Promocion;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosBiblioteca;
use Tests\TestCase;

class PagoTest extends TestCase
{
    use CreaDatosBiblioteca;
    use RefreshDatabase;

    public function test_los_pagos_requieren_autenticacion(): void
    {
        $this->get(route('pagos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_al_suscribirse_se_registra_un_pago_interno(): void
    {
        $cliente = User::factory()->cliente()->create();
        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
            'estado' => Plan::ESTADO_ACTIVA,
        ]);

        $this->actingAs($cliente)
            ->post(route('planes.suscribir', $plan))
            ->assertRedirectContains(route('planes.confirmacion', Suscripcion::firstOrFail()));

        $pago = Pago::firstOrFail();

        $this->assertSame('interno', $pago->metodo);
        $this->assertSame(Pago::ESTADO_PAGADO, $pago->estado);
        $this->assertSame('9.99', $pago->monto);
        $this->assertSame(today()->toDateString(), $pago->f_pago->toDateString());
        $this->assertStringStartsWith('PAG-', $pago->referencia);
        $this->assertSame($cliente->id, $pago->user_id);
        $this->assertSame(Suscripcion::firstOrFail()->id, $pago->suscripcion_id);
    }

    public function test_el_pago_registra_el_precio_con_promocion_vigente(): void
    {
        $cliente = User::factory()->cliente()->create();
        $plan = Plan::create([
            'tipo' => 'anual',
            'precio' => '100.00',
            'estado' => Plan::ESTADO_ACTIVA,
        ]);
        $promocion = Promocion::create([
            'cuanto' => '10',
            'f_inicio' => today()->subDay()->toDateString(),
            'f_finalizacion' => today()->addDay()->toDateString(),
            'motivo' => 'Lanzamiento',
            'estado' => 'activa',
        ]);
        $plan->promociones()->attach($promocion);

        $this->actingAs($cliente)
            ->post(route('planes.suscribir', $plan))
            ->assertRedirectContains(route('planes.confirmacion', Suscripcion::firstOrFail()));

        $this->assertSame('90.00', Pago::firstOrFail()->monto);
    }

    public function test_el_cliente_solo_ve_sus_pagos_y_puede_buscarlos(): void
    {
        $cliente = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $suscripcion = $this->activarSuscripcion($cliente, $plan);
        $this->crearPago($cliente, $suscripcion, 'PAG-CLIENTE-001', '9.99');
        $this->crearPago($otro, null, 'PAG-AJENO-002', '5.00');

        $this->actingAs($cliente)
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('PAG-CLIENTE-001')
            ->assertDontSee('PAG-AJENO-002')
            ->assertSee('Plan Mensual')
            ->assertSee('Interno')
            ->assertSee('Pagado');

        $this->actingAs($cliente)
            ->get(route('pagos.index', ['q' => 'CLIENTE']))
            ->assertOk()
            ->assertSee('PAG-CLIENTE-001')
            ->assertDontSee('PAG-AJENO-002');
    }

    public function test_el_staff_ve_todos_los_pagos_y_el_usuario(): void
    {
        $staff = User::factory()->admin()->create();
        $cliente = User::factory()->cliente()->create();

        $this->crearPago($staff, null, 'PAG-STAFF-001', '9.99');
        $this->crearPago($cliente, null, 'PAG-CLIENTE-002', '5.00');

        $this->actingAs($staff)
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('PAG-STAFF-001')
            ->assertSee('PAG-CLIENTE-002')
            ->assertSee('Usuario');
    }

    public function test_el_detalle_de_pago_muestra_la_informacion_y_respeta_permisos(): void
    {
        $cliente = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $staff = User::factory()->admin()->create();
        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
            'beneficios' => ['Lectura ilimitada'],
        ]);
        $suscripcion = $this->activarSuscripcion($cliente, $plan);
        $pago = $this->crearPago($cliente, $suscripcion, 'PAG-DETALLE-001', '9.99');

        $this->actingAs($cliente)
            ->get(route('pagos.show', $pago))
            ->assertOk()
            ->assertSee('Detalle del pago')
            ->assertSee('PAG-DETALLE-001')
            ->assertSee('Plan Mensual')
            ->assertSee('Lectura ilimitada');

        $this->actingAs($otro)
            ->get(route('pagos.show', $pago))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('pagos.show', $pago))
            ->assertOk()
            ->assertSee($cliente->email);
    }

    private function crearPago(User $usuario, ?Suscripcion $suscripcion, string $referencia, string $monto): Pago
    {
        return Pago::create([
            'user_id' => $usuario->id,
            'suscripcion_id' => $suscripcion?->id,
            'referencia' => $referencia,
            'monto' => $monto,
            'metodo' => 'interno',
            'estado' => Pago::ESTADO_PAGADO,
            'f_pago' => today()->toDateString(),
        ]);
    }
}

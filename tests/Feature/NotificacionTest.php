<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Prestamo;
use App\Models\User;
use App\Notifications\PrestamoPorVencer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Concerns\CreaDatosBiblioteca;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use CreaDatosBiblioteca;
    use RefreshDatabase;

    public function test_las_notificaciones_requieren_autenticacion(): void
    {
        $this->get(route('notificaciones.index'))
            ->assertRedirect(route('login'));
    }

    public function test_el_comando_crea_notificaciones_para_prestamos_proximos_y_vencidos(): void
    {
        Log::spy();

        $usuario = User::factory()->cliente()->create();

        $this->crearPrestamo($usuario, today()->addDays(2));
        $this->crearPrestamo($usuario, today()->subDay());
        $this->crearPrestamo($usuario, today()->addDays(5));

        $this->artisan('biblioteca:notificar-vencimientos')
            ->assertSuccessful();

        $this->assertSame(2, $usuario->notifications()->count());
        $this->assertSame(1, $usuario->notifications()->where('data->tipo', 'por_vencer')->count());
        $this->assertSame(1, $usuario->notifications()->where('data->tipo', 'vencido')->count());

        Log::shouldHaveReceived('info')->atLeast()->once();
    }

    public function test_el_comando_no_duplica_notificaciones_el_mismo_dia(): void
    {
        $usuario = User::factory()->cliente()->create();
        $this->crearPrestamo($usuario, today()->addDay());

        $this->artisan('biblioteca:notificar-vencimientos')->assertSuccessful();
        $this->artisan('biblioteca:notificar-vencimientos')->assertSuccessful();

        $this->assertSame(1, $usuario->notifications()->count());
    }

    public function test_el_usuario_ve_sus_notificaciones_y_puede_marcarlas_como_leidas(): void
    {
        $usuario = User::factory()->cliente()->create();
        $prestamo = $this->crearPrestamo($usuario, today()->addDay());

        $usuario->notify(new PrestamoPorVencer($prestamo, false, 'clave-1'));
        $usuario->notify(new PrestamoPorVencer($prestamo, false, 'clave-2'));

        $this->actingAs($usuario)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Préstamo por vencer')
            ->assertSee('Marcar todas como leídas');

        $this->assertSame(2, $usuario->unreadNotifications()->count());

        $primera = $usuario->notifications()->firstOrFail();

        $this->actingAs($usuario)
            ->post(route('notificaciones.marcar-leida', $primera))
            ->assertRedirect();

        $this->assertNotNull($primera->fresh()->read_at);
        $this->assertSame(1, $usuario->unreadNotifications()->count());

        $this->actingAs($usuario)
            ->post(route('notificaciones.marcar-leidas'))
            ->assertRedirect();

        $this->assertSame(0, $usuario->unreadNotifications()->count());
    }

    public function test_un_usuario_no_puede_marcar_la_notificacion_de_otro(): void
    {
        $dueno = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $prestamo = $this->crearPrestamo($dueno, today()->addDay());

        $dueno->notify(new PrestamoPorVencer($prestamo, false, 'clave-ajena'));
        $notificacion = $dueno->notifications()->firstOrFail();

        $this->actingAs($otro)
            ->post(route('notificaciones.marcar-leida', $notificacion))
            ->assertForbidden();

        $this->assertNull($notificacion->fresh()->read_at);
    }

    public function test_el_comando_de_renovaciones_avisay_no_duplica(): void
    {
        $usuario = User::factory()->cliente()->create();
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $usuario->suscripciones()->create([
            'plan_id' => $plan->id,
            'f_inicio' => today()->subDays(27)->toDateString(),
            'f_finalizacion' => today()->addDays(3)->toDateString(),
            'estado' => 'activa',
            'es_prueba' => true,
        ]);
        $usuario->suscripciones()->create([
            'plan_id' => $plan->id,
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addDays(20)->toDateString(),
            'estado' => 'activa',
        ]);

        $this->artisan('biblioteca:notificar-renovaciones')->assertSuccessful();
        $this->artisan('biblioteca:notificar-renovaciones')->assertSuccessful();

        $this->assertSame(1, $usuario->notifications()->count());
        $this->assertSame('renovacion', $usuario->notifications()->first()->data['tipo']);
        $this->assertSame('Prueba por finalizar', $usuario->notifications()->first()->data['titulo']);
    }

    private function crearPrestamo(User $usuario, $fin): Prestamo
    {
        $libro = $this->crearLibroConLicencia('Libro con vencimiento '.Str::random(5));

        return $usuario->prestamos()->create([
            'cantidad' => 1,
            'f_inicio' => today()->subDays(5)->toDateString(),
            'f_finalizacion' => $fin->toDateString(),
            'estado' => Prestamo::ESTADO_ACTIVO,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
        ]);
    }
}

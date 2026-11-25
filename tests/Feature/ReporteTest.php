<?php

namespace Tests\Feature;

use App\Models\Libro;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosBiblioteca;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use CreaDatosBiblioteca;
    use RefreshDatabase;

    public function test_los_reportes_requieren_autenticacion(): void
    {
        $this->get(route('reportes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_solo_el_staff_puede_ver_los_reportes(): void
    {
        $cliente = User::factory()->cliente()->create();
        $staff = User::factory()->admin()->create();

        $this->actingAs($cliente)
            ->get(route('reportes.index'))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('reportes.exportar', 'libros'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Reportes de gestión')
            ->assertSee('Descargar CSV');
    }

    public function test_puede_exportar_los_cuatro_reportes_en_csv(): void
    {
        $staff = User::factory()->admin()->create();
        $libro = $this->crearLibroConLicencia('Libro para reporte');
        $this->activarSuscripcion($staff, Plan::create(['tipo' => 'anual', 'precio' => '99.00']));
        $this->crearPrestamoPara($staff, $libro);

        $esperados = [
            'libros' => ['Título', $libro->titulo],
            'planes' => ['Cobertura', 'Plan Anual'],
            'suscripciones' => ['Usuario', 'Plan Anual'],
            'pagos' => ['Referencia', 'PAG-REPORTE-001'],
            'prestamos' => ['Días de penalización', $libro->titulo],
        ];

        foreach ($esperados as $tipo => [$cabecera, $contenido]) {
            $respuesta = $this->actingAs($staff)
                ->get(route('reportes.exportar', $tipo))
                ->assertOk()
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

            $csv = $respuesta->streamedContent();

            $this->assertStringContainsString($cabecera, $csv);
            $this->assertStringContainsString($contenido, $csv);
        }
    }

    public function test_puede_exportar_planes_y_suscripciones_en_pdf(): void
    {
        $staff = User::factory()->admin()->create();
        $this->activarSuscripcion($staff, Plan::create(['tipo' => 'anual', 'precio' => '99.00']));

        $respuesta = $this->actingAs($staff)
            ->get(route('reportes.exportar', ['tipo' => 'planes', 'formato' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        $this->assertGreaterThan(1000, strlen($respuesta->getContent()));
    }

    public function test_el_filtro_mensual_reduce_los_registros_del_reporte(): void
    {
        $staff = User::factory()->admin()->create();
        $mesActual = now()->format('Y-m');

        Libro::create([
            'titulo' => 'Libro del mes',
            'descripcion' => 'Publicado este mes.',
            'fecha_publicacion' => today()->toDateString(),
        ]);
        Libro::create([
            'titulo' => 'Libro anterior',
            'descripcion' => 'Publicado el mes pasado.',
            'fecha_publicacion' => now()->subMonth()->toDateString(),
        ]);

        $csv = $this->actingAs($staff)
            ->get(route('reportes.exportar', ['tipo' => 'libros', 'mes' => $mesActual]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Libro del mes', $csv);
        $this->assertStringNotContainsString('Libro anterior', $csv);
    }

    public function test_el_indice_de_reportes_se_puede_buscar(): void
    {
        $staff = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->get(route('reportes.index', ['q' => 'pagos']))
            ->assertOk()
            ->assertSee('Pagos')
            ->assertDontSee('Catálogo completo con autores');
    }

    public function test_un_reporte_inexistente_devuelve_not_found(): void
    {
        $staff = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->get(route('reportes.exportar', 'inexistente'))
            ->assertNotFound();
    }

    private function crearPrestamoPara(User $usuario, Libro $libro): void
    {
        $usuario->prestamos()->create([
            'cantidad' => 1,
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addDays(7)->toDateString(),
            'estado' => Prestamo::ESTADO_ACTIVO,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
        ]);

        Pago::create([
            'user_id' => $usuario->id,
            'referencia' => 'PAG-REPORTE-001',
            'monto' => '99.00',
            'metodo' => 'interno',
            'estado' => Pago::ESTADO_PAGADO,
            'f_pago' => today()->toDateString(),
        ]);
    }
}

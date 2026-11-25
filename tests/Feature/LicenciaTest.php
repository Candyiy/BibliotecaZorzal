<?php

namespace Tests\Feature;

use App\Models\Libro;
use App\Models\Licencia;
use App\Models\LicenciaEditorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class LicenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_puede_listar_crear_editar_leer_y_eliminar_licencias_editoriales(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('licencias.index'))
            ->assertOk()
            ->assertSee('command="show-modal"', false)
            ->assertSee('commandfor="crear-editorial"', false)
            ->assertSee('commandfor="crear-licencia"', false);

        $this->actingAs($usuario)
            ->post(route('licencias-editoriales.store'), [
                '_modal' => 'crear-editorial',
                'nombre' => 'Contrato editorial 2026',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->addYear()->toDateString(),
                'archivo_pdf' => UploadedFile::fake()->create('licencia.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('licencias.index'));

        $editorial = LicenciaEditorial::firstOrFail();
        $rutaPdf = $editorial->archivo_pdf;

        $this->assertSame('Contrato editorial 2026', $editorial->nombre);
        Storage::disk('local')->assertExists($rutaPdf);

        $this->actingAs($usuario)
            ->get(route('licencias.index'))
            ->assertOk()
            ->assertSee('commandfor="editar-editorial-'.$editorial->id.'"', false)
            ->assertSee('commandfor="pdf-editorial-'.$editorial->id.'"', false);

        $this->actingAs($usuario)
            ->get(route('licencias-editoriales.pdf', $editorial))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($usuario)
            ->put(route('licencias-editoriales.update', $editorial), [
                '_modal' => 'editar-editorial-'.$editorial->id,
                'nombre' => 'Contrato editorial 2027',
                'f_inicio' => '2027-01-01',
                'f_finalizacion' => '2027-06-30',
                // Simula el input de archivo vacío que envía el navegador.
                'archivo_pdf' => new SymfonyUploadedFile('', '', 'application/pdf', UPLOAD_ERR_NO_FILE, true),
            ])
            ->assertRedirect(route('licencias.index'));

        $this->assertSame('Contrato editorial 2027', $editorial->fresh()->nombre);
        $this->assertSame($rutaPdf, $editorial->fresh()->archivo_pdf);
        Storage::disk('local')->assertExists($rutaPdf);

        $this->actingAs($usuario)
            ->delete(route('licencias-editoriales.destroy', $editorial))
            ->assertRedirect(route('licencias.index'));

        $this->assertDatabaseMissing('licencias_editoriales', ['id' => $editorial->id]);
        Storage::disk('local')->assertMissing($rutaPdf);
    }

    public function test_puede_crear_una_licencia_editorial_sin_pdf(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('licencias-editoriales.store'), [
                'nombre' => 'Licencia sin documento',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->addYear()->toDateString(),
            ])
            ->assertRedirect(route('licencias.index'));

        $this->assertDatabaseHas('licencias_editoriales', [
            'nombre' => 'Licencia sin documento',
            'archivo_pdf' => null,
        ]);
    }

    public function test_puede_asignar_editar_y_eliminar_una_licencia_a_un_libro(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'El Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia A',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);
        $otraEditorial = LicenciaEditorial::create([
            'nombre' => 'Licencia B',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);

        $this->actingAs($usuario)
            ->post(route('licencias.store'), [
                '_modal' => 'crear-licencia',
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $editorial->id,
            ])
            ->assertRedirect(route('licencias.index'));

        $licencia = Licencia::firstOrFail();

        $this->assertDatabaseHas('licencias', [
            'libro_id' => $libro->id,
            'licencia_editorial_id' => $editorial->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('licencias.index'))
            ->assertOk()
            ->assertSee('commandfor="editar-licencia-'.$licencia->id.'"', false)
            ->assertSee('commandfor="eliminar-licencia-'.$licencia->id.'"', false);

        $this->actingAs($usuario)
            ->put(route('licencias.update', $licencia), [
                '_modal' => 'editar-licencia-'.$licencia->id,
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $otraEditorial->id,
            ])
            ->assertRedirect(route('licencias.index'));

        $this->assertSame($otraEditorial->id, $licencia->fresh()->licencia_editorial_id);

        $this->actingAs($usuario)
            ->delete(route('licencias.destroy', $licencia))
            ->assertRedirect(route('licencias.index'));

        $this->assertDatabaseMissing('licencias', ['id' => $licencia->id]);
    }

    public function test_no_permite_asignar_la_misma_licencia_dos_veces_al_mismo_libro(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'El Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia A',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);

        $datos = [
            'libro_id' => $libro->id,
            'licencia_editorial_id' => $editorial->id,
        ];

        $this->actingAs($usuario)
            ->post(route('licencias.store'), $datos)
            ->assertRedirect(route('licencias.index'));

        $this->actingAs($usuario)
            ->post(route('licencias.store'), $datos)
            ->assertSessionHasErrors('licencia_editorial_id');

        $this->assertSame(1, Licencia::count());
    }

    public function test_los_errores_de_validacion_reabren_el_modal_correspondiente(): void
    {
        $usuario = User::factory()->create();
        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia A',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);

        $respuestaEditorial = $this->actingAs($usuario)
            ->from(route('licencias.index'))
            ->followingRedirects()
            ->post(route('licencias-editoriales.store'), [
                '_modal' => 'crear-editorial',
                'nombre' => '',
                'f_inicio' => today()->toDateString(),
                'f_finalizacion' => today()->subDay()->toDateString(),
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-editorial"\s+open/',
            $respuestaEditorial->getContent()
        );

        $respuestaLicencia = $this->actingAs($usuario)
            ->from(route('licencias.index'))
            ->followingRedirects()
            ->post(route('licencias.store'), [
                '_modal' => 'crear-licencia',
                'libro_id' => '',
                'licencia_editorial_id' => $editorial->id,
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-licencia"\s+open/',
            $respuestaLicencia->getContent()
        );
    }

    public function test_no_permite_fecha_de_inicio_anterior_a_la_actual(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('licencias.index'))
            ->followingRedirects()
            ->post(route('licencias-editoriales.store'), [
                '_modal' => 'crear-editorial',
                'nombre' => 'Contrato vencido',
                'f_inicio' => today()->subDay()->toDateString(),
                'f_finalizacion' => today()->addMonth()->toDateString(),
            ])
            ->assertOk()
            ->assertSee('La fecha de inicio no puede ser anterior a la actual.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-editorial"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame(0, LicenciaEditorial::count());
    }

    public function test_el_formulario_de_licencia_editorial_no_permite_fechas_anteriores_desde_el_navegador(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('licencias.index'))
            ->assertOk()
            ->assertSee('min="'.today()->toDateString().'"', false);
    }

    public function test_el_formulario_de_licencias_incluye_el_campo_de_cantidad_de_lecturas(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('licencias.index'))
            ->assertOk()
            ->assertSee('Cantidad de lecturas')
            ->assertSee('name="cantidad_lecturas"', false)
            ->assertSee('placeholder="Sin límite"', false)
            ->assertSee('Días de préstamo')
            ->assertSee('name="dias_prestamo"', false)
            ->assertSee('Precio del préstamo')
            ->assertSee('name="precio_prestamo"', false);
    }

    public function test_puede_guardar_actualizar_y_dejar_sin_limite_el_contador_de_lecturas(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Libro contado',
            'descripcion' => 'Descripción del libro contado.',
            'fecha_publicacion' => '2020-01-01',
        ]);
        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia A',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);

        $this->actingAs($usuario)
            ->post(route('licencias.store'), [
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $editorial->id,
                'cantidad_lecturas' => '5',
                'dias_prestamo' => '15',
                'precio_prestamo' => '8.75',
            ])
            ->assertRedirect(route('licencias.index'));

        $licencia = Licencia::firstOrFail();
        $this->assertSame(5, $licencia->cantidad_lecturas);
        $this->assertSame(15, $licencia->dias_prestamo);
        $this->assertSame('8.75', (string) $licencia->precio_prestamo);

        $this->actingAs($usuario)
            ->put(route('licencias.update', $licencia), [
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $editorial->id,
                'cantidad_lecturas' => '3',
            ])
            ->assertRedirect(route('licencias.index'));
        $this->assertSame(3, $licencia->fresh()->cantidad_lecturas);

        $this->actingAs($usuario)
            ->put(route('licencias.update', $licencia), [
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $editorial->id,
                'cantidad_lecturas' => '',
            ])
            ->assertRedirect(route('licencias.index'));
        $this->assertNull($licencia->fresh()->cantidad_lecturas);
    }

    public function test_el_contador_de_lecturas_rechaza_valores_negativos(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Libro contado',
            'descripcion' => 'Descripción del libro contado.',
            'fecha_publicacion' => '2020-01-01',
        ]);
        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia A',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);

        $this->actingAs($usuario)
            ->post(route('licencias.store'), [
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $editorial->id,
                'cantidad_lecturas' => -1,
            ])
            ->assertSessionHasErrors('cantidad_lecturas');

        $this->assertSame(0, Licencia::count());
    }

    public function test_los_dias_y_el_precio_del_prestamo_se_validan(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Libro contado',
            'descripcion' => 'Descripción del libro contado.',
            'fecha_publicacion' => '2020-01-01',
        ]);
        $editorial = LicenciaEditorial::create([
            'nombre' => 'Licencia A',
            'f_inicio' => '2026-01-01',
            'f_finalizacion' => '2026-12-31',
        ]);

        $this->actingAs($usuario)
            ->post(route('licencias.store'), [
                'libro_id' => $libro->id,
                'licencia_editorial_id' => $editorial->id,
                'cantidad_lecturas' => 2,
                'dias_prestamo' => 0,
                'precio_prestamo' => -1,
            ])
            ->assertSessionHasErrors(['dias_prestamo', 'precio_prestamo']);

        $this->assertSame(0, Licencia::count());
    }
}

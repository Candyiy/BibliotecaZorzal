<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Libro;
use App\Models\Plan;
use App\Models\Prestamo;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\Concerns\CreaDatosBiblioteca;
use Tests\TestCase;

class LibroTest extends TestCase
{
    use CreaDatosBiblioteca;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_puede_listar_crear_y_leer_un_libro_con_pdf(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('libros.index'))
            ->assertOk();

        $this->actingAs($usuario)
            ->post(route('libros.store'), [
                'titulo' => 'Don Quijote',
                'descripcion' => 'Novela clásica de Cervantes.',
                'fecha_publicacion' => '1605-01-01',
                'archivo_pdf' => UploadedFile::fake()->create('quijote.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('libros.index'));

        $libro = Libro::firstOrFail();

        $this->assertSame('Don Quijote', $libro->titulo);
        Storage::disk('local')->assertExists($libro->archivo_pdf);

        $this->actingAs($usuario)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('href="'.route('libros.leer', $libro).'?v=', false)
            ->assertDontSee('leer-libro-'.$libro->id, false)
            ->assertSee('commandfor="editar-'.$libro->id.'"', false)
            ->assertSee('<dialog id="editar-'.$libro->id.'"', false);

        $respuesta = $this->actingAs($usuario)
            ->get(route('libros.leer', $libro))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
    }

    public function test_un_invitado_no_puede_leer_el_pdf_y_va_al_login(): void
    {
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica de Cervantes.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => 'libros/quijote.pdf',
        ]);

        $this->get(route('libros.leer', $libro))
            ->assertRedirect(route('login'));
    }

    public function test_un_cliente_sin_suscripcion_no_puede_leer_ni_descargar(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica de Cervantes.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => 'libros/quijote.pdf',
        ]);

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('libros.descargar', $libro))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Suscribirme')
            ->assertDontSee('href="'.route('libros.leer', $libro).'?v=', false);
    }

    public function test_lista_muestra_prestamo_cuando_el_libro_tiene_un_plan_por_dias(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia('Libro con plan por días');
        $libro->update(['archivo_pdf' => 'libros/plan-por-dias.pdf']);

        Plan::create([
            'tipo' => 'dias',
            'duracion_dias' => 10,
            'precio' => '4.99',
            'modalidad' => Plan::MODALIDAD_LIBRO,
            'libro_id' => $libro->id,
        ]);

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Préstamo')
            ->assertDontSee('Suscribirme');
    }

    public function test_un_cliente_con_suscripcion_activa_puede_leer_y_descargar(): void
    {
        $cliente = User::factory()->cliente()->create();
        Storage::disk('local')->put('libros/quijote.pdf', 'contenido del pdf');

        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica de Cervantes.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => 'libros/quijote.pdf',
        ]);

        $this->suscribirCliente($cliente, today()->addMonth()->toDateString());

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($cliente)
            ->get(route('libros.descargar', $libro))
            ->assertOk();

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('href="'.route('libros.leer', $libro).'?v=', false);
    }

    public function test_un_cliente_con_suscripcion_vencida_no_puede_leer(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica de Cervantes.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => 'libros/quijote.pdf',
        ]);

        $this->suscribirCliente($cliente, today()->subDay()->toDateString());

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertForbidden();
    }

    public function test_los_errores_de_validacion_reabren_el_modal_correspondiente(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->post(route('libros.store'), [
                '_modal' => 'crear',
                'titulo' => '',
                'descripcion' => 'Descripción',
                'fecha_publicacion' => '2024-01-01',
                'archivo_pdf' => UploadedFile::fake()->create('no-es-pdf.txt', 1, 'text/plain'),
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-libro"\s+open/',
            $respuesta->getContent()
        );
    }

    public function test_puede_editar_y_eliminar_un_libro(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Título original',
            'descripcion' => 'Descripción original.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        $this->actingAs($usuario)
            ->put(route('libros.update', $libro), [
                'titulo' => 'Título actualizado',
                'descripcion' => 'Descripción actualizada.',
                'fecha_publicacion' => '2021-02-03',
                // Simula el input de archivo vacío que envía el navegador.
                'archivo_pdf' => new SymfonyUploadedFile('', '', 'application/pdf', UPLOAD_ERR_NO_FILE, true),
            ])
            ->assertRedirect(route('libros.index'));

        $this->assertSame('Título actualizado', $libro->fresh()->titulo);

        $this->actingAs($usuario)
            ->delete(route('libros.destroy', $libro))
            ->assertRedirect(route('libros.index'));

        $this->assertDatabaseMissing('libros', ['id' => $libro->id]);
    }

    public function test_puede_asignar_y_quitar_un_autor_opcional_en_un_libro(): void
    {
        $usuario = User::factory()->create();
        $autor = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);
        $otroAutor = Autor::create([
            'nombre' => 'Gabriel García Márquez',
            'nacionalidad' => 'Colombiana',
            'f_nacimiento' => '1927-03-06',
        ]);

        $datosLibro = [
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => UploadedFile::fake()->create('quijote.pdf', 20, 'application/pdf'),
        ];

        $this->actingAs($usuario)
            ->post(route('libros.store'), $datosLibro + ['autor_id' => $autor->id])
            ->assertRedirect(route('libros.index'));

        $libro = Libro::firstOrFail();

        $this->assertTrue($libro->autores->contains($autor));

        $this->actingAs($usuario)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Miguel de Cervantes')
            ->assertSee('name="autor_id"', false);

        $this->actingAs($usuario)
            ->put(route('libros.update', $libro), [
                'titulo' => 'Don Quijote',
                'descripcion' => 'Novela clásica.',
                'fecha_publicacion' => '1605-01-01',
                'autor_id' => $otroAutor->id,
                'archivo_pdf' => new SymfonyUploadedFile('', '', 'application/pdf', UPLOAD_ERR_NO_FILE, true),
            ])
            ->assertRedirect(route('libros.index'));

        $this->assertTrue($libro->fresh()->autores->contains($otroAutor));
        $this->assertFalse($libro->fresh()->autores->contains($autor));

        $this->actingAs($usuario)
            ->put(route('libros.update', $libro), [
                'titulo' => 'Don Quijote',
                'descripcion' => 'Novela clásica.',
                'fecha_publicacion' => '1605-01-01',
                'autor_id' => '',
                'archivo_pdf' => new SymfonyUploadedFile('', '', 'application/pdf', UPLOAD_ERR_NO_FILE, true),
            ])
            ->assertRedirect(route('libros.index'));

        $this->assertCount(0, $libro->fresh()->autores);
    }

    public function test_puede_reemplazar_el_pdf_al_editar(): void
    {
        $usuario = User::factory()->create();
        Storage::disk('local')->put('libros/viejo.pdf', 'contenido viejo');

        $libro = Libro::create([
            'titulo' => 'Libro',
            'descripcion' => 'Descripción.',
            'fecha_publicacion' => '2020-01-01',
            'archivo_pdf' => 'libros/viejo.pdf',
        ]);

        $this->actingAs($usuario)
            ->put(route('libros.update', $libro), [
                'titulo' => 'Libro',
                'descripcion' => 'Descripción.',
                'fecha_publicacion' => '2020-01-01',
                'archivo_pdf' => UploadedFile::fake()->create('nuevo.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('libros.index'));

        $rutaNueva = $libro->fresh()->archivo_pdf;

        $this->assertNotSame('libros/viejo.pdf', $rutaNueva);
        Storage::disk('local')->assertMissing('libros/viejo.pdf');
        Storage::disk('local')->assertExists($rutaNueva);
    }

    public function test_la_validacion_de_edicion_reabre_el_modal_del_libro(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->put(route('libros.update', $libro), [
                '_modal' => 'editar-'.$libro->id,
                'titulo' => '',
                'descripcion' => 'Novela clásica.',
                'fecha_publicacion' => '1605-01-01',
                'autor_id' => '',
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false)
            ->assertSee('El campo título es obligatorio.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="editar-'.$libro->id.'"\s+open/',
            $respuesta->getContent()
        );

        $this->assertSame('Don Quijote', $libro->fresh()->titulo);
    }

    public function test_la_lista_de_libros_permite_ver_los_datos_del_autor(): void
    {
        $cliente = User::factory()->cliente()->create();
        $autor = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $libro->autores()->attach($autor);

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('commandfor="ver-autor-'.$autor->id.'"', false)
            ->assertSee('<dialog id="ver-autor-'.$autor->id.'"', false)
            ->assertSee('Española')
            ->assertSee('29/09/1547')
            ->assertSee('Don Quijote')
            ->assertSee('Datos del autor');
    }

    public function test_puede_asignar_actualizar_y_quitar_categorias_de_un_libro(): void
    {
        $usuario = User::factory()->create();
        $novela = Categoria::create(['nombre' => 'Novela']);
        $cuento = Categoria::create(['nombre' => 'Cuento']);
        $historia = Categoria::create(['nombre' => 'Historia']);

        $this->actingAs($usuario)
            ->post(route('libros.store'), [
                'titulo' => 'Don Quijote',
                'descripcion' => 'Novela clásica.',
                'fecha_publicacion' => '1605-01-01',
                'categorias' => [$novela->id, $cuento->id],
                'archivo_pdf' => UploadedFile::fake()->create('quijote.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('libros.index'));

        $libro = Libro::firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$novela->id, $cuento->id],
            $libro->categorias->pluck('id')->all()
        );

        $this->actingAs($usuario)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('id="editar-categorias-'.$libro->id.'"', false)
            ->assertSee('value="'.$historia->id.'"', false);

        $datosEdicion = [
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => new SymfonyUploadedFile('', '', 'application/pdf', UPLOAD_ERR_NO_FILE, true),
        ];

        $this->actingAs($usuario)
            ->put(route('libros.update', $libro), $datosEdicion + ['categorias' => [$historia->id]])
            ->assertRedirect(route('libros.index'));

        $this->assertEqualsCanonicalizing([$historia->id], $libro->fresh()->categorias->pluck('id')->all());

        $this->actingAs($usuario)
            ->put(route('libros.update', $libro), $datosEdicion)
            ->assertRedirect(route('libros.index'));

        $this->assertCount(0, $libro->fresh()->categorias);
    }

    public function test_puede_crear_una_categoria_desde_el_catalogo(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('categorias.store'), ['nombre' => 'Novela histórica'])
            ->assertRedirect(route('libros.index'));

        $this->assertDatabaseHas('categorias', ['nombre' => 'Novela histórica']);
    }

    public function test_no_acepta_numeros_en_el_nombre_de_la_categoria(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->post(route('categorias.store'), [
                '_modal' => 'crear-categoria',
                'nombre' => 'Novela histórica 2',
            ])
            ->assertOk()
            ->assertSee('El campo categoría no debe contener números.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-categoria"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame(0, Categoria::count());
    }

    public function test_no_se_pueden_crear_categorias_duplicadas_y_reabre_el_modal(): void
    {
        $usuario = User::factory()->create();
        Categoria::create(['nombre' => 'Novela']);

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->post(route('categorias.store'), [
                '_modal' => 'crear-categoria',
                'nombre' => 'Novela',
            ])
            ->assertOk()
            ->assertSee('Esta categoría ya existe.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-categoria"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame(1, Categoria::count());
    }

    public function test_un_cliente_no_puede_crear_categorias(): void
    {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)
            ->post(route('categorias.store'), ['nombre' => 'Cuento'])
            ->assertForbidden();
    }

    public function test_no_acepta_numeros_en_titulo_o_descripcion_al_crear(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->post(route('libros.store'), [
                '_modal' => 'crear',
                'titulo' => 'Don Quijote 2',
                'descripcion' => 'Novela clásica publicada en 1605.',
                'fecha_publicacion' => '1605-01-01',
                'archivo_pdf' => UploadedFile::fake()->create('quijote.pdf', 20, 'application/pdf'),
            ])
            ->assertOk()
            ->assertSee('El título no debe contener números.', false)
            ->assertSee('La descripción no debe contener números.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-libro"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame(0, Libro::count());
    }

    public function test_no_acepta_numeros_en_titulo_o_descripcion_al_editar(): void
    {
        $usuario = User::factory()->create();
        $libro = Libro::create([
            'titulo' => 'Título original',
            'descripcion' => 'Descripción original.',
            'fecha_publicacion' => '2020-01-01',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->put(route('libros.update', $libro), [
                '_modal' => 'editar-'.$libro->id,
                'titulo' => 'Título actualizado 2',
                'descripcion' => 'Descripción con 2024.',
                'fecha_publicacion' => '2021-02-03',
                'archivo_pdf' => new SymfonyUploadedFile('', '', 'application/pdf', UPLOAD_ERR_NO_FILE, true),
            ])
            ->assertOk()
            ->assertSee('El título no debe contener números.', false)
            ->assertSee('La descripción no debe contener números.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="editar-'.$libro->id.'"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame('Título original', $libro->fresh()->titulo);
        $this->assertSame('Descripción original.', $libro->fresh()->descripcion);
    }

    public function test_un_prestamo_vencido_bloquea_la_lectura_y_muestra_la_regularizacion(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->suscribirCliente($cliente, today()->addMonth()->toDateString());
        $libro = $this->crearLibroConLicencia('Libro con préstamo vencido');
        $libro->update(['archivo_pdf' => 'libros/vencido.pdf']);
        Storage::disk('local')->put('libros/vencido.pdf', 'contenido del pdf');

        $cliente->prestamos()->create([
            'cantidad' => 1,
            'f_inicio' => today()->subDays(10)->toDateString(),
            'f_finalizacion' => today()->subDay()->toDateString(),
            'estado' => Prestamo::ESTADO_ACTIVO,
            'licencia_id' => $libro->licencias()->firstOrFail()->id,
        ]);

        $this->actingAs($cliente)
            ->get(route('libros.leer', $libro))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('libros.descargar', $libro))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Acceso bloqueado')
            ->assertSee('Regularizar acceso')
            ->assertDontSee('href="'.route('libros.leer', $libro).'?v=', false);
    }

    public function test_una_penalizacion_vigente_bloquea_la_lectura(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->suscribirCliente($cliente, today()->addMonth()->toDateString());
        $libro = $this->crearLibroConLicencia('Libro con penalización');
        $libro->update(['archivo_pdf' => 'libros/penalizado.pdf']);
        Storage::disk('local')->put('libros/penalizado.pdf', 'contenido del pdf');

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
            ->get(route('libros.leer', $libro))
            ->assertForbidden();
    }

    public function test_un_plan_por_libro_solo_da_acceso_al_libro_incluido(): void
    {
        $cliente = User::factory()->cliente()->create();
        $permitido = $this->crearLibroConLicencia('Libro incluido en el plan');
        $ajeno = $this->crearLibroConLicencia('Libro fuera del plan');

        foreach (['libros/incluido.pdf' => $permitido, 'libros/ajeno.pdf' => $ajeno] as $ruta => $libro) {
            $libro->update(['archivo_pdf' => $ruta]);
            Storage::disk('local')->put($ruta, 'contenido del pdf');
        }

        $plan = Plan::create([
            'tipo' => 'dias',
            'duracion_dias' => 15,
            'precio' => '9.99',
            'modalidad' => Plan::MODALIDAD_LIBRO,
            'libro_id' => $permitido->id,
        ]);

        $cliente->suscripciones()->create([
            'plan_id' => $plan->id,
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);

        $this->actingAs($cliente)
            ->get(route('libros.leer', $permitido))
            ->assertOk();

        $this->actingAs($cliente)
            ->get(route('libros.leer', $ajeno))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Fuera de tu plan')
            ->assertSee('Cambiar plan')
            ->assertSee('href="'.route('libros.leer', $permitido).'?v=', false);
    }

    private function suscribirCliente(User $cliente, string $finalizacion): void
    {
        $plan = Plan::create([
            'tipo' => 'mensual',
            'precio' => '9.99',
        ]);

        Suscripcion::create([
            'user_id' => $cliente->id,
            'plan_id' => $plan->id,
            'f_inicio' => today()->subMonth()->toDateString(),
            'f_finalizacion' => $finalizacion,
            'estado' => 'activa',
        ]);
    }
}

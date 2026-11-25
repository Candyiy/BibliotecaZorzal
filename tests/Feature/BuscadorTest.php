<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Libro;
use App\Models\Licencia;
use App\Models\LicenciaEditorial;
use App\Models\Plan;
use App\Models\Promocion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuscadorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_el_buscador_de_libros_filtra_por_titulo_y_autor(): void
    {
        $usuario = User::factory()->create();
        $autor = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);
        $quijote = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $quijote->autores()->attach($autor);
        Libro::create([
            'titulo' => 'Cien años de soledad',
            'descripcion' => 'Otra novela.',
            'fecha_publicacion' => '1967-01-01',
        ]);

        $this->actingAs($usuario)
            ->get(route('libros.index', ['q' => 'Quijote']))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($quijote))
            ->assertSee('1 resultado para');

        $this->actingAs($usuario)
            ->get(route('libros.index', ['q' => 'Cervantes']))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($quijote))
            ->assertSee('1 resultado para');
    }

    public function test_el_buscador_de_usuarios_filtra_por_nombre_y_correo(): void
    {
        $admin = User::factory()->create();
        $ana = User::factory()->create(['name' => 'Ana García', 'email' => 'ana@ejemplo.test']);
        $luis = User::factory()->create(['name' => 'Luis Pérez', 'email' => 'luis@ejemplo.test']);

        $this->actingAs($admin)
            ->get(route('usuarios.index', ['q' => 'ana@']))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->total() === 1
                && $usuarios->getCollection()->first()->is($ana))
            ->assertSee('1 resultado para');

        $this->actingAs($admin)
            ->get(route('usuarios.index', ['q' => 'Luis']))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->total() === 1
                && $usuarios->getCollection()->first()->is($luis))
            ->assertSee('1 resultado para');
    }

    public function test_el_buscador_de_autores_filtra_por_nacionalidad(): void
    {
        $usuario = User::factory()->create();
        $cervantes = Autor::create(['nombre' => 'Miguel de Cervantes', 'nacionalidad' => 'Española', 'f_nacimiento' => '1547-09-29']);
        $garcia = Autor::create(['nombre' => 'Gabriel García Márquez', 'nacionalidad' => 'Colombiana', 'f_nacimiento' => '1927-03-06']);

        $this->actingAs($usuario)
            ->get(route('autores.index', ['q' => 'Colombiana']))
            ->assertOk()
            ->assertViewHas('autores', fn ($autores) => $autores->count() === 1
                && $autores->first()->is($garcia))
            ->assertSee('1 resultado para');

        $this->assertEquals(1, Autor::whereKey($cervantes->id)->count());
    }

    public function test_el_buscador_de_licencias_filtra_por_contrato_y_libro(): void
    {
        $usuario = User::factory()->create();
        $planeta = LicenciaEditorial::create([
            'nombre' => 'Editorial Planeta',
            'f_inicio' => '2020-01-01',
            'f_finalizacion' => '2030-01-01',
        ]);
        $anagrama = LicenciaEditorial::create([
            'nombre' => 'Editorial Anagrama',
            'f_inicio' => '2020-01-01',
            'f_finalizacion' => '2030-01-01',
        ]);
        $quijote = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $celestina = Libro::create([
            'titulo' => 'La celestina',
            'descripcion' => 'Obra dramática.',
            'fecha_publicacion' => '1499-01-01',
        ]);
        $licenciaPlaneta = Licencia::create([
            'libro_id' => $quijote->id,
            'licencia_editorial_id' => $planeta->id,
        ]);
        $licenciaAnagrama = Licencia::create([
            'libro_id' => $celestina->id,
            'licencia_editorial_id' => $anagrama->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('licencias.index', ['q' => 'Planeta']))
            ->assertOk()
            ->assertViewHas('editoriales', fn ($editoriales) => $editoriales->count() === 1
                && $editoriales->first()->is($planeta))
            ->assertViewHas('licencias', fn ($licencias) => $licencias->count() === 1
                && $licencias->first()->is($licenciaPlaneta))
            ->assertSee('2 resultados para');

        $this->actingAs($usuario)
            ->get(route('licencias.index', ['q' => 'celestina']))
            ->assertOk()
            ->assertViewHas('editoriales', fn ($editoriales) => $editoriales->isEmpty())
            ->assertViewHas('licencias', fn ($licencias) => $licencias->count() === 1
                && $licencias->first()->is($licenciaAnagrama))
            ->assertSee('1 resultado para');
    }

    public function test_el_buscador_de_planes_y_promociones_filtra(): void
    {
        $usuario = User::factory()->create();
        $mensual = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);
        $anual = Plan::create(['tipo' => 'anual', 'precio' => '99.99']);

        $verano = Promocion::create([
            'motivo' => 'Oferta de verano',
            'cuanto' => '2.00',
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);
        $verano->planes()->attach($anual);
        $invierno = Promocion::create([
            'motivo' => 'Descuento de invierno',
            'cuanto' => '3.00',
            'f_inicio' => today()->toDateString(),
            'f_finalizacion' => today()->addMonth()->toDateString(),
            'estado' => 'activa',
        ]);

        $this->actingAs($usuario)
            ->get(route('planes.index', ['q' => 'anual']))
            ->assertOk()
            ->assertViewHas('planes', fn ($planes) => $planes->count() === 1
                && $planes->first()->is($anual))
            ->assertSee('1 resultado para');

        $this->actingAs($usuario)
            ->get(route('promociones.index', ['q' => 'verano']))
            ->assertOk()
            ->assertViewHas('promociones', fn ($promociones) => $promociones->count() === 1
                && $promociones->first()->is($verano))
            ->assertSee('1 resultado para');

        $this->actingAs($usuario)
            ->get(route('promociones.index', ['q' => 'anual']))
            ->assertOk()
            ->assertViewHas('promociones', fn ($promociones) => $promociones->count() === 1
                && $promociones->first()->is($verano))
            ->assertSee('1 resultado para');

        $this->assertEquals(1, Promocion::whereKey($invierno->id)->count());
    }

    public function test_el_buscador_de_historial_filtra_solo_lecturas_del_usuario(): void
    {
        $usuario = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();
        $quijote = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $celestina = Libro::create([
            'titulo' => 'La celestina',
            'descripcion' => 'Obra dramática.',
            'fecha_publicacion' => '1499-01-01',
        ]);

        $lectura = $usuario->lecturas()->create(['libro_id' => $quijote->id]);
        $usuario->lecturas()->create(['libro_id' => $celestina->id]);
        $otro->lecturas()->create(['libro_id' => $celestina->id]);

        $this->actingAs($usuario)
            ->get(route('historial.index', ['q' => 'Quijote']))
            ->assertOk()
            ->assertViewHas('lecturas', fn ($lecturas) => $lecturas->total() === 1
                && $lecturas->getCollection()->first()->is($lectura))
            ->assertSee('1 resultado para');
    }

    /**
     * @return array<string, mixed>
     */
    private function prepararCatalogoFiltrable(): array
    {
        $cervantes = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);
        $garcia = Autor::create([
            'nombre' => 'Gabriel García Márquez',
            'nacionalidad' => 'Colombiana',
            'f_nacimiento' => '1927-03-06',
        ]);
        $novela = Categoria::create(['nombre' => 'Novela']);
        $cuento = Categoria::create(['nombre' => 'Cuento']);

        $quijote = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => 'libros/quijote.pdf',
        ]);
        $quijote->autores()->attach($cervantes);
        $quijote->categorias()->attach($novela);

        $celestina = Libro::create([
            'titulo' => 'La celestina',
            'descripcion' => 'Obra dramática.',
            'fecha_publicacion' => '1499-01-01',
        ]);
        $celestina->autores()->attach($garcia);
        $celestina->categorias()->attach($cuento);

        $novelaSinPdf = Libro::create([
            'titulo' => 'La novela sin PDF',
            'descripcion' => 'Otra novela.',
            'fecha_publicacion' => '1605-06-01',
        ]);
        $novelaSinPdf->categorias()->attach($novela);

        return compact('cervantes', 'garcia', 'novela', 'cuento', 'quijote', 'celestina', 'novelaSinPdf');
    }

    public function test_el_catalogo_filtra_por_categoria(): void
    {
        $usuario = User::factory()->create();
        $datos = $this->prepararCatalogoFiltrable();

        $this->actingAs($usuario)
            ->get(route('libros.index', ['categoria' => $datos['novela']->id]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 2)
            ->assertSee('Todas las categorías')
            ->assertSee('value="'.$datos['novela']->id.'" selected', false);

        $this->actingAs($usuario)
            ->get(route('libros.index', ['categoria' => $datos['cuento']->id]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($datos['celestina']));
    }

    public function test_el_catalogo_filtra_por_autor(): void
    {
        $usuario = User::factory()->create();
        $datos = $this->prepararCatalogoFiltrable();

        $this->actingAs($usuario)
            ->get(route('libros.index', ['autor' => $datos['cervantes']->id]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($datos['quijote']))
            ->assertSee('Todos los autores');

        $this->actingAs($usuario)
            ->get(route('libros.index', ['autor' => $datos['garcia']->id]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($datos['celestina']));
    }

    public function test_el_catalogo_filtra_por_anio(): void
    {
        $usuario = User::factory()->create();
        $datos = $this->prepararCatalogoFiltrable();

        $this->actingAs($usuario)
            ->get(route('libros.index', ['anio' => 1605]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 2)
            ->assertSee('Todos los años');

        $this->actingAs($usuario)
            ->get(route('libros.index', ['anio' => 1499]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($datos['celestina']));
    }

    public function test_el_catalogo_filtra_por_disponibilidad(): void
    {
        $usuario = User::factory()->create();
        $datos = $this->prepararCatalogoFiltrable();

        $this->actingAs($usuario)
            ->get(route('libros.index', ['disponibilidad' => 'con_pdf']))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($datos['quijote']))
            ->assertSee('Con PDF');

        $this->actingAs($usuario)
            ->get(route('libros.index', ['disponibilidad' => 'sin_pdf']))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 2)
            ->assertSee('Sin PDF');
    }

    public function test_los_filtros_se_combinan_con_la_busqueda_y_se_pueden_limpiar(): void
    {
        $usuario = User::factory()->create();
        $datos = $this->prepararCatalogoFiltrable();

        $this->actingAs($usuario)
            ->get(route('libros.index', [
                'q' => 'Quijote',
                'categoria' => $datos['novela']->id,
                'autor' => $datos['cervantes']->id,
                'anio' => 1605,
                'disponibilidad' => 'con_pdf',
            ]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 1
                && $libros->getCollection()->first()->is($datos['quijote']))
            ->assertSee('1 resultado para')
            ->assertSee('Limpiar');
    }

    public function test_la_paginacion_conserva_los_filtros(): void
    {
        $usuario = User::factory()->create();
        $datos = $this->prepararCatalogoFiltrable();

        for ($i = 1; $i <= 12; $i++) {
            $libro = Libro::create([
                'titulo' => 'Novela '.$i,
                'descripcion' => 'Libro de prueba.',
                'fecha_publicacion' => '1605-01-01',
                'archivo_pdf' => 'libros/novela-'.$i.'.pdf',
            ]);
            $libro->categorias()->attach($datos['novela']);
        }

        $this->actingAs($usuario)
            ->get(route('libros.index', [
                'categoria' => $datos['novela']->id,
                'disponibilidad' => 'con_pdf',
                'page' => 2,
            ]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 13
                && $libros->currentPage() === 2)
            ->assertSee('?categoria='.$datos['novela']->id.'&amp;disponibilidad=con_pdf&amp;page=1', false)
            ->assertSee('Limpiar');
    }

    public function test_los_filtros_con_valores_invalidados_se_ignoran(): void
    {
        $usuario = User::factory()->create();
        $this->prepararCatalogoFiltrable();

        $this->actingAs($usuario)
            ->get(route('libros.index', [
                'categoria' => 99999,
                'autor' => 'no-valido',
                'anio' => 9999,
                'disponibilidad' => 'raro',
            ]))
            ->assertOk()
            ->assertViewHas('libros', fn ($libros) => $libros->total() === 3);
    }
}

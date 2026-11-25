<?php

namespace Tests\Feature;

use App\Models\Lectura;
use App\Models\Libro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_abrir_un_libro_registra_la_lectura_y_aparece_en_el_historial(): void
    {
        $usuario = User::factory()->create();
        $libro = $this->libro('Don Quijote', 'libros/quijote.pdf');
        Storage::disk('local')->put('libros/quijote.pdf', '%PDF-1.4');

        $this->actingAs($usuario)
            ->get(route('libros.leer', $libro))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('lecturas', [
            'user_id' => $usuario->id,
            'libro_id' => $libro->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('historial.index'))
            ->assertOk()
            ->assertSee('Historial de lectura')
            ->assertSee('Don Quijote')
            ->assertSee('Has abierto 1 libro')
            ->assertSee('Última lectura')
            ->assertSee('Primera lectura');
    }

    public function test_reabrir_un_libro_no_duplica_el_registro_y_actualiza_la_ultima_lectura(): void
    {
        $usuario = User::factory()->create();
        $libro = $this->libro('Don Quijote', 'libros/quijote.pdf');
        Storage::disk('local')->put('libros/quijote.pdf', '%PDF-1.4');

        $this->actingAs($usuario)->get(route('libros.leer', $libro));

        $lectura = Lectura::firstOrFail();
        $fechaOriginal = $lectura->updated_at;

        $this->travel(2)->days();

        $this->actingAs($usuario)->get(route('libros.leer', $libro));

        $this->assertSame(1, Lectura::count());
        $this->assertTrue($lectura->fresh()->updated_at->greaterThan($fechaOriginal));
    }

    public function test_el_historial_solo_muestra_los_libros_del_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $libro = $this->libro('Libro del usuario');
        $libroAjeno = $this->libro('Libro de otro usuario');

        Lectura::create(['user_id' => $usuario->id, 'libro_id' => $libro->id]);
        Lectura::create(['user_id' => $otroUsuario->id, 'libro_id' => $libroAjeno->id]);

        $this->actingAs($usuario)
            ->get(route('historial.index'))
            ->assertOk()
            ->assertSee('Libro del usuario')
            ->assertDontSee('Libro de otro usuario')
            ->assertSee('Has abierto 1 libro');
    }

    public function test_el_historial_requiere_autenticacion(): void
    {
        $this->get(route('historial.index'))->assertRedirect(route('login'));
    }

    private function libro(string $titulo, ?string $pdf = null): Libro
    {
        return Libro::create([
            'titulo' => $titulo,
            'descripcion' => 'Descripción del libro.',
            'fecha_publicacion' => '1605-01-01',
            'archivo_pdf' => $pdf,
        ]);
    }
}

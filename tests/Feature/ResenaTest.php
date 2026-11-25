<?php

namespace Tests\Feature;

use App\Models\Libro;
use App\Models\Resena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_puntuar_y_comentar_un_libro(): void
    {
        $usuario = User::factory()->create();
        $libro = $this->libro();

        $this->actingAs($usuario)
            ->post(route('resenas.store', $libro), [
                '_modal' => 'resenas-'.$libro->id,
                'puntaje' => 5,
                'comentario' => 'Una obra maestra de la literatura.',
            ])
            ->assertRedirect(route('libros.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('resenas', [
            'libro_id' => $libro->id,
            'user_id' => $usuario->id,
            'puntaje' => 5,
            'comentario' => 'Una obra maestra de la literatura.',
        ]);

        $this->actingAs($usuario)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Calificación')
            ->assertSee('5,0 / 5')
            ->assertSee('1 reseña')
            ->assertSee('Una obra maestra de la literatura.')
            ->assertSee('commandfor="resenas-'.$libro->id.'"', false)
            ->assertSee('<dialog id="resenas-'.$libro->id.'"', false)
            ->assertSee('name="puntaje"', false);
    }

    public function test_la_calificacion_muestra_el_promedio_y_el_porcentaje_de_todas_las_resenas(): void
    {
        $usuario = User::factory()->create();
        $otro = User::factory()->create();
        $libro = $this->libro();

        Resena::create(['libro_id' => $libro->id, 'user_id' => $usuario->id, 'puntaje' => 5, 'comentario' => 'Excelente.']);
        Resena::create(['libro_id' => $libro->id, 'user_id' => $otro->id, 'puntaje' => 3, 'comentario' => 'Interesante.']);

        $this->actingAs($usuario)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('4,0 / 5 · 80%')
            ->assertSee('2 reseñas')
            ->assertSee('Excelente.')
            ->assertSee('Interesante.');
    }

    public function test_cada_usuario_tiene_su_propia_resena_y_puede_actualizarla(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $luis = User::factory()->create(['name' => 'Luis']);
        $libro = $this->libro();

        $this->actingAs($ana)
            ->post(route('resenas.store', $libro), [
                'puntaje' => 5,
                'comentario' => 'La reseña de Ana.',
            ])
            ->assertRedirect(route('libros.index'));

        $this->actingAs($luis)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Todavía no has reseñado este libro.')
            ->assertSee('La reseña de Ana.')
            ->assertDontSee('Tu reseña · 5/5');

        $this->actingAs($luis)
            ->post(route('resenas.store', $libro), [
                'puntaje' => 2,
                'comentario' => 'La reseña de Luis.',
            ])
            ->assertRedirect(route('libros.index'));

        $this->assertDatabaseCount('resenas', 2);
        $this->assertDatabaseHas('resenas', [
            'libro_id' => $libro->id,
            'user_id' => $ana->id,
            'puntaje' => 5,
            'comentario' => 'La reseña de Ana.',
        ]);
        $this->assertDatabaseHas('resenas', [
            'libro_id' => $libro->id,
            'user_id' => $luis->id,
            'puntaje' => 2,
            'comentario' => 'La reseña de Luis.',
        ]);

        $this->actingAs($luis)
            ->post(route('resenas.store', $libro), [
                'puntaje' => 4,
                'comentario' => 'Luis cambió de opinión.',
            ])
            ->assertRedirect(route('libros.index'));

        $this->assertDatabaseCount('resenas', 2);
        $this->assertDatabaseHas('resenas', [
            'libro_id' => $libro->id,
            'user_id' => $luis->id,
            'puntaje' => 4,
            'comentario' => 'Luis cambió de opinión.',
        ]);

        $this->actingAs($luis)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Tu reseña · 4/5 (80%)')
            ->assertSee('La reseña de Ana.');
    }

    public function test_valida_el_comentario_y_el_puntaje_y_reabre_el_modal(): void
    {
        $usuario = User::factory()->create();
        $libro = $this->libro();

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->post(route('resenas.store', $libro), [
                '_modal' => 'resenas-'.$libro->id,
                'puntaje' => 6,
                'comentario' => '',
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false)
            ->assertSee('El campo comentario es obligatorio.', false)
            ->assertSee('El puntaje debe estar entre 1 y 5.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="resenas-'.$libro->id.'"\s+open/',
            $respuesta->getContent()
        );

        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_no_acepta_numeros_en_el_comentario(): void
    {
        $usuario = User::factory()->create();
        $libro = $this->libro();

        $respuesta = $this->actingAs($usuario)
            ->from(route('libros.index'))
            ->followingRedirects()
            ->post(route('resenas.store', $libro), [
                '_modal' => 'resenas-'.$libro->id,
                'puntaje' => 5,
                'comentario' => 'Excelente libro 2026.',
            ])
            ->assertOk()
            ->assertSee('El comentario no debe contener números.', false)
            ->assertSee('data-sin-numeros', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="resenas-'.$libro->id.'"\s+open/',
            $respuesta->getContent()
        );
        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_hacer_una_resena_requiere_autenticacion(): void
    {
        $libro = $this->libro();

        $this->post(route('resenas.store', $libro), [
            'puntaje' => 4,
            'comentario' => 'Sin sesión.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('resenas', 0);
    }

    private function libro(): Libro
    {
        return Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Novela clásica de Cervantes.',
            'fecha_publicacion' => '1605-01-01',
        ]);
    }
}

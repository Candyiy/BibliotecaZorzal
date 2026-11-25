<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\Libro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_crear_editar_y_eliminar_un_autor(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('autores.index'))
            ->assertOk()
            ->assertSee('command="show-modal"', false)
            ->assertSee('commandfor="crear-autor"', false);

        $this->actingAs($usuario)
            ->post(route('autores.store'), [
                '_modal' => 'crear',
                'nombre' => 'Miguel de Cervantes',
                'nacionalidad' => 'Española',
                'f_nacimiento' => '1547-09-29',
            ])
            ->assertRedirect(route('autores.index'));

        $autor = Autor::firstOrFail();

        $this->assertDatabaseHas('autores', [
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
        ]);
        $this->assertSame('1547-09-29', $autor->f_nacimiento->format('Y-m-d'));

        $this->actingAs($usuario)
            ->get(route('autores.index'))
            ->assertOk()
            ->assertSee('commandfor="editar-'.$autor->id.'"', false)
            ->assertSee('<dialog id="editar-'.$autor->id.'"', false)
            ->assertSee('commandfor="eliminar-autor-'.$autor->id.'"', false);

        $this->actingAs($usuario)
            ->put(route('autores.update', $autor), [
                '_modal' => 'editar-'.$autor->id,
                'nombre' => 'Miguel de Cervantes Saavedra',
                'nacionalidad' => 'Español',
                'f_nacimiento' => '1547-09-30',
            ])
            ->assertRedirect(route('autores.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Miguel de Cervantes Saavedra', $autor->fresh()->nombre);
        $this->assertSame('Español', $autor->fresh()->nacionalidad);

        $this->actingAs($usuario)
            ->delete(route('autores.destroy', $autor))
            ->assertRedirect(route('autores.index'));

        $this->assertDatabaseMissing('autores', ['id' => $autor->id]);
    }

    public function test_los_errores_de_validacion_reabren_el_modal_correspondiente(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('autores.index'))
            ->followingRedirects()
            ->post(route('autores.store'), [
                '_modal' => 'crear',
                'nombre' => '',
                'nacionalidad' => 'Española',
                'f_nacimiento' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-autor"\s+open/',
            $respuesta->getContent()
        );
    }

    public function test_la_validacion_de_edicion_reabre_el_modal_del_autor(): void
    {
        $usuario = User::factory()->create();
        $autor = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->from(route('autores.index'))
            ->followingRedirects()
            ->put(route('autores.update', $autor), [
                '_modal' => 'editar-'.$autor->id,
                'nombre' => '',
                'nacionalidad' => 'Española',
                'f_nacimiento' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertOk()
            ->assertSee('Revisa los datos:', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="editar-'.$autor->id.'"\s+open/',
            $respuesta->getContent()
        );

        $this->assertSame('Miguel de Cervantes', $autor->fresh()->nombre);
    }

    public function test_muestra_los_libros_asociados_al_autor(): void
    {
        $usuario = User::factory()->create();
        $autor = Autor::create([
            'nombre' => 'Gabriel García Márquez',
            'nacionalidad' => 'Colombiana',
            'f_nacimiento' => '1927-03-06',
        ]);
        $libro = Libro::create([
            'titulo' => 'Cien años de soledad',
            'descripcion' => 'Novela del realismo mágico.',
            'fecha_publicacion' => '1967-05-30',
        ]);

        $autor->libros()->attach($libro);

        $this->actingAs($usuario)
            ->get(route('autores.index'))
            ->assertOk()
            ->assertSee('Cien años de soledad')
            ->assertSee('1 libro');
    }

    public function test_no_acepta_numeros_en_nombre_o_nacionalidad_al_crear(): void
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->from(route('autores.index'))
            ->followingRedirects()
            ->post(route('autores.store'), [
                '_modal' => 'crear',
                'nombre' => 'Miguel de Cervantes 2',
                'nacionalidad' => 'Española 1500',
                'f_nacimiento' => '1547-09-29',
            ])
            ->assertOk()
            ->assertSee('El nombre no debe contener números.', false)
            ->assertSee('La nacionalidad no debe contener números.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="crear-autor"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame(0, Autor::count());
    }

    public function test_no_acepta_numeros_en_nombre_o_nacionalidad_al_editar(): void
    {
        $usuario = User::factory()->create();
        $autor = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);

        $respuesta = $this->actingAs($usuario)
            ->from(route('autores.index'))
            ->followingRedirects()
            ->put(route('autores.update', $autor), [
                '_modal' => 'editar-'.$autor->id,
                'nombre' => 'Miguel de Cervantes 2',
                'nacionalidad' => 'Española 1605',
                'f_nacimiento' => '1547-09-30',
            ])
            ->assertOk()
            ->assertSee('El nombre no debe contener números.', false)
            ->assertSee('La nacionalidad no debe contener números.', false);

        $this->assertMatchesRegularExpression(
            '/<dialog id="editar-'.$autor->id.'"\s+open/',
            $respuesta->getContent()
        );
        $this->assertSame('Miguel de Cervantes', $autor->fresh()->nombre);
        $this->assertSame('Española', $autor->fresh()->nacionalidad);
    }
}

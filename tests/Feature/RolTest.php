<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\Libro;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_cliente_puede_ver_libros_autores_historial_y_planes(): void
    {
        $cliente = User::factory()->cliente()->create();
        Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Descripción del libro.',
            'fecha_publicacion' => '1605-01-01',
        ]);

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('Reseñar')
            ->assertDontSee('Nuevo libro')
            ->assertDontSee('commandfor="editar-', false);

        $this->actingAs($cliente)
            ->get(route('autores.index'))
            ->assertOk()
            ->assertDontSee('Nuevo autor')
            ->assertDontSee('commandfor="editar-', false);

        $this->actingAs($cliente)
            ->get(route('historial.index'))
            ->assertOk();

        $this->actingAs($cliente)
            ->get(route('planes.index'))
            ->assertOk()
            ->assertDontSee('Nuevo plan')
            ->assertDontSee('Promoción');
    }

    public function test_el_cliente_no_puede_acceder_a_modulos_de_gestion(): void
    {
        $cliente = User::factory()->cliente()->create();

        foreach ([
            route('dashboard'),
            route('usuarios.index'),
            route('promociones.index'),
            route('licencias.index'),
        ] as $url) {
            $this->actingAs($cliente)->get($url)->assertForbidden();
        }
    }

    public function test_el_cliente_no_puede_crear_ni_editar_contenido_de_gestion(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Descripción del libro.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $autor = Autor::create([
            'nombre' => 'Miguel de Cervantes',
            'nacionalidad' => 'Española',
            'f_nacimiento' => '1547-09-29',
        ]);
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $this->actingAs($cliente)->post(route('libros.store'), [])->assertForbidden();
        $this->actingAs($cliente)->put(route('libros.update', $libro), [])->assertForbidden();
        $this->actingAs($cliente)->delete(route('libros.destroy', $libro))->assertForbidden();
        $this->actingAs($cliente)->post(route('autores.store'), [])->assertForbidden();
        $this->actingAs($cliente)->put(route('autores.update', $autor), [])->assertForbidden();
        $this->actingAs($cliente)->delete(route('autores.destroy', $autor))->assertForbidden();
        $this->actingAs($cliente)->post(route('planes.store'), [])->assertForbidden();
        $this->actingAs($cliente)->post(route('usuarios.store'), [])->assertForbidden();
        $this->actingAs($cliente)->post(route('promociones.store'), [])->assertForbidden();
    }

    public function test_el_cliente_puede_suscribirse_y_resenar(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = Libro::create([
            'titulo' => 'Don Quijote',
            'descripcion' => 'Descripción del libro.',
            'fecha_publicacion' => '1605-01-01',
        ]);
        $plan = Plan::create(['tipo' => 'mensual', 'precio' => '9.99']);

        $respuesta = $this->actingAs($cliente)
            ->post(route('planes.suscribir', $plan));

        $respuesta->assertRedirect(route('planes.confirmacion', Suscripcion::firstOrFail()));

        $this->assertDatabaseHas('suscripciones', [
            'user_id' => $cliente->id,
            'plan_id' => $plan->id,
            'estado' => 'activa',
        ]);

        $this->actingAs($cliente)
            ->post(route('resenas.store', $libro), [
                'comentario' => 'Una lectura excelente.',
                'puntaje' => 5,
            ])
            ->assertRedirect(route('libros.index'));

        $this->assertDatabaseHas('resenas', [
            'libro_id' => $libro->id,
            'user_id' => $cliente->id,
            'comentario' => 'Una lectura excelente.',
            'puntaje' => 5,
        ]);
    }

    public function test_el_cliente_va_directo_a_libros_despues_de_iniciar_sesion(): void
    {
        $cliente = User::factory()->cliente()->create();

        $this->post(route('login.store'), [
            'email' => $cliente->email,
            'password' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('libros.index', absolute: false));
    }

    public function test_el_administrador_entra_a_libros_y_sigue_accediendo_a_la_gestion(): void
    {
        $administrador = User::factory()->admin()->create();

        $this->actingAs($administrador)->get(route('dashboard'))->assertRedirect(route('libros.index'));
        $this->actingAs($administrador)->get(route('usuarios.index'))->assertOk();
        $this->actingAs($administrador)->get(route('promociones.index'))->assertOk();
        $this->actingAs($administrador)->get(route('licencias.index'))->assertOk();
    }
}

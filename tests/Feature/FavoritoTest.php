<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosBiblioteca;
use Tests\TestCase;

class FavoritoTest extends TestCase
{
    use CreaDatosBiblioteca;
    use RefreshDatabase;

    public function test_los_favoritos_requieren_autenticacion(): void
    {
        $this->get(route('favoritos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_puede_agregar_y_quitar_un_libro_de_favoritos(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia('Libro favorito');

        $this->actingAs($cliente)
            ->post(route('favoritos.store', $libro))
            ->assertRedirect();

        $this->assertDatabaseHas('favoritos', [
            'user_id' => $cliente->id,
            'libro_id' => $libro->id,
        ]);

        $this->actingAs($cliente)
            ->delete(route('favoritos.destroy', $libro))
            ->assertRedirect();

        $this->assertDatabaseMissing('favoritos', [
            'user_id' => $cliente->id,
            'libro_id' => $libro->id,
        ]);
    }

    public function test_agregar_dos_veces_el_mismo_libro_no_duplica_el_favorito(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia();

        $this->actingAs($cliente)
            ->post(route('favoritos.store', $libro))
            ->assertRedirect();

        $this->actingAs($cliente)
            ->post(route('favoritos.store', $libro))
            ->assertRedirect();

        $this->assertSame(1, $cliente->favoritos()->count());
    }

    public function test_el_catalogo_muestra_el_estado_de_favorito_del_usuario(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia('Libro marcado');
        $cliente->favoritos()->create(['libro_id' => $libro->id]);

        $this->actingAs($cliente)
            ->get(route('libros.index'))
            ->assertOk()
            ->assertSee('En favoritos')
            ->assertSee(route('favoritos.destroy', $libro), false);
    }

    public function test_el_listado_solo_muestra_los_favoritos_propios_y_se_puede_buscar(): void
    {
        $cliente = User::factory()->cliente()->create();
        $otro = User::factory()->cliente()->create();

        $propio = $this->crearLibroConLicencia('Poemas elegidos');
        $ajeno = $this->crearLibroConLicencia('Cuaderno ajeno');

        $cliente->favoritos()->create(['libro_id' => $propio->id]);
        $otro->favoritos()->create(['libro_id' => $ajeno->id]);

        $this->actingAs($cliente)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee('Poemas elegidos')
            ->assertDontSee('Cuaderno ajeno');

        $this->actingAs($cliente)
            ->get(route('favoritos.index', ['q' => 'Poemas']))
            ->assertOk()
            ->assertSee('Poemas elegidos')
            ->assertDontSee('Cuaderno ajeno');
    }
}

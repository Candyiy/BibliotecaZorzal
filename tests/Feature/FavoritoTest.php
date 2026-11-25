<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_el_enlace_de_lectura_del_favorito_no_usa_navegacion_livewire(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);

        $libro = $this->crearLibroConLicencia('Libro favorito para leer');
        $libro->update(['archivo_pdf' => 'libros/favorito.pdf']);
        Storage::disk('local')->put('libros/favorito.pdf', 'contenido del pdf');
        $cliente->favoritos()->create(['libro_id' => $libro->id]);

        $contenido = $this->actingAs($cliente)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee(route('libros.leer', $libro).'?v=', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('libros.leer', $libro), '/').'\?v=[^"]+"\s+class="btn btn-secondary btn-sm"/',
            $contenido
        );
    }

    public function test_un_favorito_fuera_del_plan_muestra_cambiar_plan_en_lugar_de_leer(): void
    {
        $cliente = User::factory()->cliente()->create();
        $libro = $this->crearLibroConLicencia('Libro fuera del plan');
        $libro->update(['archivo_pdf' => 'libros/fuera.pdf']);
        $cliente->favoritos()->create(['libro_id' => $libro->id]);

        $libroDelPlan = $this->crearLibroConLicencia('Libro incluido en el plan');
        $plan = Plan::create([
            'tipo' => 'dias',
            'duracion_dias' => 15,
            'precio' => '9.99',
            'modalidad' => Plan::MODALIDAD_LIBRO,
            'libro_id' => $libroDelPlan->id,
        ]);
        $this->activarSuscripcion($cliente, $plan);

        $this->actingAs($cliente)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee('Fuera de tu plan')
            ->assertSee('Cambiar plan')
            ->assertDontSee(route('libros.leer', $libro).'?v=', false);
    }

    public function test_un_favorito_con_acceso_bloqueado_muestra_regularizar(): void
    {
        $cliente = User::factory()->cliente()->create();
        $this->activarSuscripcion($cliente);

        $libro = $this->crearLibroConLicencia('Libro con penalizacion');
        $libro->update(['archivo_pdf' => 'libros/penalizado.pdf']);
        $cliente->favoritos()->create(['libro_id' => $libro->id]);

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
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee('Acceso bloqueado')
            ->assertSee('Regularizar acceso')
            ->assertDontSee(route('libros.leer', $libro).'?v=', false);
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

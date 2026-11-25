<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_precarga_el_catalogo_sin_duplicados(): void
    {
        $this->seed(CategoriaSeeder::class);

        $cantidadInicial = Categoria::count();

        $this->assertGreaterThan(0, $cantidadInicial);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Novela']);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Ciencia ficción']);

        $this->seed(CategoriaSeeder::class);

        $this->assertSame($cantidadInicial, Categoria::count());
    }
}

<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Catálogo base para no empezar creando categorías desde cero.
     */
    private const CATEGORIAS = [
        'Arte',
        'Biografía',
        'Ciencia ficción',
        'Cuento',
        'Drama',
        'Ensayo',
        'Filosofía',
        'Historia',
        'Infantil',
        'Juvenil',
        'Misterio',
        'Novela',
        'Poesía',
        'Romance',
        'Viajes',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }
    }
}

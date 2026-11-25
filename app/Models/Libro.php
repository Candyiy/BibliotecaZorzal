<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_publicacion',
        'archivo_pdf',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date',
        ];
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class);
    }

    public function resenaDeUsuario(int $userId): ?Resena
    {
        return $this->resenas->firstWhere('user_id', $userId);
    }

    public function porcentajeResenas(): int
    {
        $promedio = $this->resenas_avg_puntaje ?? $this->resenas->avg('puntaje');

        return (int) round(((float) $promedio) * 20);
    }

    public function licencias()
    {
        return $this->hasMany(Licencia::class);
    }

    public function autores()
    {
        return $this->belongsToMany(
            Autor::class,
            'pertenece',
            'libro_id',
            'autor_id'
        );
    }

    public function categorias()
    {
        return $this->belongsToMany(
            Categoria::class,
            'libro_categoria',
            'libro_id',
            'categoria_id'
        );
    }

    public function lecturas()
    {
        return $this->hasMany(Lectura::class);
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class);
    }
}

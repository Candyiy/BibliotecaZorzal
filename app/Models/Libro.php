<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    /**
     * @return Collection<int, Licencia>
     */
    private function licenciasCargadas()
    {
        return $this->relationLoaded('licencias') ? $this->licencias : $this->licencias()->get();
    }

    public function usaContadorLecturas(): bool
    {
        return $this->licenciasCargadas()->contains(
            fn (Licencia $licencia): bool => $licencia->tieneContador()
        );
    }

    public function lecturasDisponibles(): int
    {
        return (int) $this->licenciasCargadas()->sum(
            fn (Licencia $licencia): int => $licencia->lecturasDisponibles()
        );
    }

    public function lecturasAgotadas(): bool
    {
        return $this->usaContadorLecturas() && $this->lecturasDisponibles() === 0;
    }

    public function licenciaParaPrestamo(): ?Licencia
    {
        $licencias = $this->licenciasCargadas();

        return $licencias->first(
            fn (Licencia $licencia): bool => $licencia->tieneContador() && ! $licencia->agotada()
        ) ?? ($this->usaContadorLecturas() ? null : $licencias->first());
    }

    /**
     * @return Collection<int, Prestamo>
     */
    public function prestamosActivos()
    {
        $licencias = $this->licenciasCargadas();
        $ids = $licencias->pluck('id')->all();

        if ($ids === []) {
            return new Collection;
        }

        if ($licencias->contains(fn (Licencia $licencia): bool => $licencia->relationLoaded('prestamos'))) {
            return $licencias->pluck('prestamos')
                ->flatten()
                ->filter(fn (Prestamo $prestamo): bool => $prestamo->estado === Prestamo::ESTADO_ACTIVO)
                ->values();
        }

        return Prestamo::query()->activos()->whereIn('licencia_id', $ids)->get();
    }

    public function prestamoActivo(): ?Prestamo
    {
        return $this->prestamosActivos()->first();
    }

    public function prestamoActivoDe(int $userId): ?Prestamo
    {
        return $this->prestamosActivos()->firstWhere('user_id', $userId);
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

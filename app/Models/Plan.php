<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_INACTIVA = 'inactiva';

    public const MODALIDAD_GENERAL = 'general';

    public const MODALIDAD_LIBRO = 'libro';

    public const MODALIDAD_PAQUETE = 'paquete';

    public const TIPO_MENSUAL = 'mensual';

    public const TIPO_ANUAL = 'anual';

    public const TIPO_DIAS = 'dias';

    public const DIAS_POR_DEFECTO = 30;

    protected $table = 'planes';

    protected $fillable = [
        'precio',
        'tipo',
        'estado',
        'modalidad',
        'libro_id',
        'cantidad_libros',
        'trial_dias',
        'duracion_dias',
        'beneficios',
    ];

    protected $attributes = [
        'estado' => self::ESTADO_ACTIVA,
        'modalidad' => self::MODALIDAD_GENERAL,
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'trial_dias' => 'integer',
            'duracion_dias' => 'integer',
            'cantidad_libros' => 'integer',
            'beneficios' => 'array',
        ];
    }

    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    public function esAnual(): bool
    {
        return $this->tipo === self::TIPO_ANUAL;
    }

    public function esPorDias(): bool
    {
        return $this->tipo === self::TIPO_DIAS || $this->modalidad === self::MODALIDAD_LIBRO;
    }

    public function duracionEnDias(): int
    {
        return max(1, (int) ($this->duracion_dias ?? self::DIAS_POR_DEFECTO));
    }

    public function duracionEnMeses(): int
    {
        return $this->esAnual() ? 12 : 1;
    }

    public function duracionEtiqueta(): string
    {
        return $this->esPorDias()
            ? $this->duracionEnDias().' '.($this->duracionEnDias() === 1 ? 'día' : 'días')
            : ($this->esAnual() ? '1 año' : '1 mes');
    }

    public function tipoEtiqueta(): string
    {
        return $this->esPorDias()
            ? 'Por días'
            : ($this->esAnual() ? 'Anual' : 'Mensual');
    }

    public function periodoPrecioEtiqueta(): string
    {
        return $this->esPorDias()
            ? 'por '.$this->duracionEtiqueta()
            : ($this->esAnual() ? 'por año' : 'por mes');
    }

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function promociones()
    {
        return $this->belongsToMany(
            Promocion::class,
            'promocion_plan',
            'plan_id',
            'promocion_id'
        );
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class);
    }

    public function libros()
    {
        return $this->belongsToMany(Libro::class, 'plan_libro', 'plan_id', 'libro_id');
    }

    public function modalidadEtiqueta(): string
    {
        return match ($this->modalidad) {
            self::MODALIDAD_LIBRO => 'Por libro',
            self::MODALIDAD_PAQUETE => 'Paquete',
            default => 'Catálogo completo',
        };
    }

    public function coberturaEtiqueta(): string
    {
        if ($this->modalidad === self::MODALIDAD_LIBRO) {
            return $this->libro?->titulo ?? 'Libro eliminado';
        }

        if ($this->modalidad === self::MODALIDAD_PAQUETE) {
            $libros = $this->relationLoaded('libros') ? $this->libros : $this->libros()->get();
            $cantidad = $libros->count();

            return $cantidad.' '.($cantidad === 1 ? 'libro' : 'libros');
        }

        return 'Todo el catálogo';
    }

    public function incluyeLibro(Libro $libro): bool
    {
        if ($this->modalidad === self::MODALIDAD_LIBRO) {
            return (int) $this->libro_id === $libro->id;
        }

        if ($this->modalidad === self::MODALIDAD_PAQUETE) {
            $libros = $this->relationLoaded('libros') ? $this->libros : $this->libros()->get();

            return $libros->contains(fn (Libro $libroPlan): bool => $libroPlan->id === $libro->id);
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function beneficiosLista(): array
    {
        return collect($this->beneficios ?? [])
            ->map(fn ($beneficio): string => trim((string) $beneficio))
            ->filter()
            ->values()
            ->all();
    }

    public function tienePrueba(): bool
    {
        return (int) ($this->trial_dias ?? 0) > 0;
    }

    public function promocionActiva(): ?Promocion
    {
        return $this->promociones
            ->first(fn (Promocion $promocion) => $promocion->esVigente());
    }

    public function precioFinal(): float
    {
        $promocion = $this->promocionActiva();

        return $promocion ? $promocion->aplicarA($this->precio) : (float) $this->precio;
    }
}

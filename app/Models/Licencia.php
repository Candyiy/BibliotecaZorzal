<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    use HasFactory;

    public const DIAS_PRESTAMO_POR_DEFECTO = 24;

    protected $fillable = [
        'libro_id',
        'licencia_editorial_id',
        'cantidad_lecturas',
        'dias_prestamo',
        'precio_prestamo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_lecturas' => 'integer',
            'dias_prestamo' => 'integer',
            'precio_prestamo' => 'decimal:2',
        ];
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class);
    }

    public function editorial()
    {
        return $this->belongsTo(LicenciaEditorial::class, 'licencia_editorial_id');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    public function tieneContador(): bool
    {
        return $this->cantidad_lecturas !== null;
    }

    public function lecturasDisponibles(): int
    {
        return $this->tieneContador() ? max(0, (int) $this->cantidad_lecturas) : 0;
    }

    public function agotada(): bool
    {
        return $this->tieneContador() && $this->lecturasDisponibles() === 0;
    }

    public function diasPrestamo(): int
    {
        return max(1, (int) ($this->dias_prestamo ?? self::DIAS_PRESTAMO_POR_DEFECTO));
    }

    public function precioPrestamo(): float
    {
        return (float) ($this->precio_prestamo ?? 0);
    }

    public function etiquetaPrestamo(): string
    {
        return number_format($this->precioPrestamo(), 2, ',', '.').' · '.$this->diasPrestamo().' días';
    }

    /**
     * Descuenta una lectura de forma atómica. Devuelve false si ya se agotó.
     */
    public function consumirLectura(): bool
    {
        return static::query()
            ->whereKey($this->id)
            ->where('cantidad_lecturas', '>', 0)
            ->decrement('cantidad_lecturas') > 0;
    }
}

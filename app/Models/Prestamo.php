<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_DEVUELTO = 'devuelto';

    public const MAX_ACTIVOS = 3;

    public const DIAS_PLAZO = 7;

    public const MAX_RENOVACIONES = 1;

    protected $fillable = [
        'cantidad',
        'f_inicio',
        'f_finalizacion',
        'estado',
        'f_devolucion',
        'dias_penalizacion',
        'renovaciones',
        'user_id',
        'licencia_id',
    ];

    protected function casts(): array
    {
        return [
            'f_inicio' => 'date',
            'f_finalizacion' => 'date',
            'f_devolucion' => 'date',
            'dias_penalizacion' => 'integer',
            'renovaciones' => 'integer',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function licencia()
    {
        return $this->belongsTo(Licencia::class);
    }

    public function libro()
    {
        return $this->hasOneThrough(Libro::class, Licencia::class, 'id', 'id', 'licencia_id', 'libro_id');
    }

    public function scopeActivos(Builder $consulta): void
    {
        $consulta->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeVencidos(Builder $consulta): void
    {
        $consulta->activos()->whereDate('f_finalizacion', '<', today());
    }

    public function estaVencido(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && $this->f_finalizacion?->lt(today()) === true;
    }

    public function diasRetraso(): int
    {
        if (! $this->f_finalizacion) {
            return 0;
        }

        $fin = $this->f_devolucion ?? today();

        return max(0, (int) $this->f_finalizacion->diffInDays($fin, false));
    }

    public function devolver(): void
    {
        $dias = $this->diasRetraso();

        $this->update([
            'estado' => self::ESTADO_DEVUELTO,
            'f_devolucion' => today()->toDateString(),
            'dias_penalizacion' => $dias,
        ]);
    }

    public function puedeRenovarse(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && ! $this->estaVencido()
            && (int) $this->renovaciones < self::MAX_RENOVACIONES;
    }

    public function renovar(): void
    {
        $this->update([
            'f_finalizacion' => $this->f_finalizacion->copy()->addDays(self::DIAS_PLAZO)->toDateString(),
            'renovaciones' => (int) $this->renovaciones + 1,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    use HasFactory;

    protected $table = 'promociones';

    protected $fillable = [
        'cuanto',
        'f_inicio',
        'f_finalizacion',
        'motivo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cuanto' => 'decimal:2',
            'f_inicio' => 'date',
            'f_finalizacion' => 'date',
        ];
    }

    public function planes()
    {
        return $this->belongsToMany(
            Plan::class,
            'promocion_plan',
            'promocion_id',
            'plan_id'
        );
    }

    public function esVigente(): bool
    {
        return strtolower($this->estado ?? '') === 'activa'
            && $this->f_inicio?->lessThanOrEqualTo(today())
            && $this->f_finalizacion?->greaterThanOrEqualTo(today());
    }

    public function porcentaje(): float
    {
        return (float) $this->cuanto;
    }

    public function etiquetaDescuento(): string
    {
        return number_format($this->porcentaje(), 2, ',', '.').' %';
    }

    public function montoDescontado(float|string $precio): float
    {
        return round((float) $precio * $this->porcentaje() / 100, 2);
    }

    public function aplicarA(float|string $precio): float
    {
        return round(max(0, (float) $precio - $this->montoDescontado($precio)), 2);
    }
}

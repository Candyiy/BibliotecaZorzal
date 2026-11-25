<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    protected $fillable = [
        'user_id',
        'plan_id',
        'f_inicio',
        'f_finalizacion',
        'estado',
        'es_prueba',
    ];

    protected function casts(): array
    {
        return [
            'f_inicio' => 'date',
            'f_finalizacion' => 'date',
            'es_prueba' => 'boolean',
        ];
    }

    public function esPrueba(): bool
    {
        return (bool) $this->es_prueba;
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}

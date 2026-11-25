<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    public const ESTADO_PAGADO = 'pagado';

    public const ESTADO_REEMBOLSADO = 'reembolsado';

    public const ESTADO_PRUEBA = 'prueba';

    protected $table = 'pagos';

    protected $fillable = [
        'user_id',
        'suscripcion_id',
        'referencia',
        'monto',
        'metodo',
        'estado',
        'f_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'f_pago' => 'date',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class);
    }
}

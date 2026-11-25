<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'libro_id',
        'licencia_editorial_id',
    ];

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
}

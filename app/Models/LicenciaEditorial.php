<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenciaEditorial extends Model
{
    use HasFactory;

    protected $table = 'licencias_editoriales';

    protected $fillable = [
        'nombre',
        'f_inicio',
        'f_finalizacion',
        'archivo_pdf',
    ];

    protected function casts(): array
    {
        return [
            'f_inicio' => 'date',
            'f_finalizacion' => 'date',
        ];
    }

    public function licencias()
    {
        return $this->hasMany(Licencia::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autor extends Model
{
    use HasFactory;

    protected $table = 'autores';

    protected $fillable = [
        'nombre',
        'nacionalidad',
        'f_nacimiento',
    ];

    protected function casts(): array
    {
        return [
            'f_nacimiento' => 'date',
        ];
    }

    public function libros()
    {
        return $this->belongsToMany(
            Libro::class,
            'pertenece',
            'autor_id',
            'libro_id'
        );
    }
}

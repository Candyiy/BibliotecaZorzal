<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    use HasFactory;

    protected $fillable = [
        'comentario',
        'puntaje',
        'libro_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'puntaje' => 'integer',
        ];
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

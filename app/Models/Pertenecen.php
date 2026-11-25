<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pertenecen extends Model
{
    use HasFactory;

    protected $table = 'pertenece';

    protected $fillable = [
        'libro_id',
        'autor_id',
    ];
}

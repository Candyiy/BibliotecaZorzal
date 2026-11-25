<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromocionPlan extends Model
{
    use HasFactory;

    protected $table = 'promocion_plan';

    protected $fillable = [
        'promocion_id',
        'plan_id',
    ];
}

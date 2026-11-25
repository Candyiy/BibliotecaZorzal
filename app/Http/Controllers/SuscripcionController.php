<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use Illuminate\Http\Request;

class SuscripcionController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $consulta = $this->filtrar(
            $request->user()->suscripciones()->getQuery(),
            $this->terminoDeBusqueda($request),
            ['estado'],
            ['plan' => ['tipo', 'modalidad']],
        );

        $suscripciones = $consulta->with('plan')
            ->latest('f_inicio')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('suscripciones.index', compact('suscripciones'));
    }
}

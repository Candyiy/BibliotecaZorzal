<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $termino = $this->terminoDeBusqueda($request);

        $consulta = $this->filtrar(
            $request->user()->lecturas()->getQuery(),
            $termino,
            [],
            ['libro' => ['titulo', 'descripcion'], 'libro.autores' => ['nombre']],
        );

        $lecturas = $consulta
            ->with(['libro' => fn ($consulta) => $consulta->withAvg('resenas', 'puntaje')->withCount('resenas')])
            ->with('libro.autores')
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('historial.index', [
            'lecturas' => $lecturas,
            'totalLibros' => $request->user()->lecturas()->count(),
        ]);
    }
}

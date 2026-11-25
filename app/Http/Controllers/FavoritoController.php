<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Favorito;
use App\Models\Libro;
use Illuminate\Http\Request;

class FavoritoController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $consulta = Favorito::where('user_id', $request->user()->id);

        $this->filtrar(
            $consulta,
            $this->terminoDeBusqueda($request),
            [],
            ['libro' => ['titulo', 'descripcion'], 'libro.autores' => ['nombre']],
        );

        $favoritos = $consulta->with('libro.autores', 'libro.licencias.prestamos')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('favoritos.index', compact('favoritos'));
    }

    public function store(Request $request, Libro $libro)
    {
        $request->user()->favoritos()->firstOrCreate(['libro_id' => $libro->id]);

        return back()->with('status', 'Libro agregado a favoritos.');
    }

    public function destroy(Request $request, Libro $libro)
    {
        $request->user()->favoritos()->where('libro_id', $libro->id)->delete();

        return back()->with('status', 'Libro quitado de favoritos.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Autor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AutorController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $termino = $this->terminoDeBusqueda($request);

        $consulta = $this->filtrar(Autor::query(), $termino, ['nombre', 'nacionalidad']);

        return view('autores.index', [
            'autores' => $consulta->with([
                'libros' => fn ($consulta) => $consulta->orderBy('titulo'),
            ])->withCount('libros')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        Autor::create($this->validar($request));

        return redirect()->route('autores.index')->with('status', 'Autor agregado correctamente.');
    }

    public function update(Request $request, Autor $autor)
    {
        $autor->update($this->validar($request));

        return redirect()->route('autores.index')->with('status', 'Autor actualizado correctamente.');
    }

    public function destroy(Autor $autor)
    {
        $autor->delete();

        return redirect()->route('autores.index')->with('status', 'Autor eliminado correctamente.');
    }

    private function validar(Request $request): array
    {
        return Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[^0-9]*$/'],
            'nacionalidad' => ['required', 'string', 'max:255', 'regex:/^[^0-9]*$/'],
            'f_nacimiento' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.regex' => 'El nombre no debe contener números.',
            'nacionalidad.max' => 'La nacionalidad no debe superar los 255 caracteres.',
            'nacionalidad.regex' => 'La nacionalidad no debe contener números.',
        ], [
            'nombre' => 'nombre',
            'nacionalidad' => 'nacionalidad',
            'f_nacimiento' => 'fecha de nacimiento',
        ])->validate();
    }
}

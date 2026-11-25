<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Libro;
use App\Models\Licencia;
use App\Models\LicenciaEditorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LicenciaController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $termino = $this->terminoDeBusqueda($request);

        $editoriales = $this->filtrar(LicenciaEditorial::query(), $termino, ['nombre']);
        $licencias = $this->filtrar(
            Licencia::query(),
            $termino,
            [],
            ['libro' => ['titulo'], 'editorial' => ['nombre']],
        );

        return view('licencias.index', [
            'editoriales' => $editoriales->withCount('licencias')->latest()->get(),
            'licencias' => $licencias->with(['libro', 'editorial'])->latest()->get(),
            'libros' => Libro::orderBy('titulo')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        Licencia::create($datos);

        return redirect()->route('licencias.index')->with('status', 'Licencia asignada correctamente.');
    }

    public function update(Request $request, Licencia $licencia)
    {
        $licencia->update($this->validar($request, $licencia));

        return redirect()->route('licencias.index')->with('status', 'Licencia actualizada correctamente.');
    }

    public function destroy(Licencia $licencia)
    {
        $licencia->delete();

        return redirect()->route('licencias.index')->with('status', 'Licencia eliminada correctamente.');
    }

    private function validar(Request $request, ?Licencia $licencia = null): array
    {
        $unica = Rule::unique('licencias', 'licencia_editorial_id')
            ->where('libro_id', $request->input('libro_id'));

        if ($licencia) {
            $unica->ignore($licencia->id);
        }

        return Validator::make($request->all(), [
            'libro_id' => ['required', 'exists:libros,id'],
            'licencia_editorial_id' => ['required', 'exists:licencias_editoriales,id', $unica],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El :attribute seleccionado no es válido.',
            'libro_id.unique' => 'Este libro ya tiene esa licencia asignada.',
            'licencia_editorial_id.unique' => 'Este libro ya tiene esa licencia asignada.',
        ], [
            'libro_id' => 'libro',
            'licencia_editorial_id' => 'licencia editorial',
        ])->validate();
    }
}

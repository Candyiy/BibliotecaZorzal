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

        $validados = Validator::make($request->all(), [
            'libro_id' => ['required', 'exists:libros,id'],
            'licencia_editorial_id' => ['required', 'exists:licencias_editoriales,id', $unica],
            'cantidad_lecturas' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'dias_prestamo' => ['nullable', 'integer', 'min:1', 'max:365'],
            'precio_prestamo' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El :attribute seleccionado no es válido.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min' => 'El campo :attribute no puede ser menor que :min.',
            'max' => 'El campo :attribute no puede superar :max.',
            'libro_id.unique' => 'Este libro ya tiene esa licencia asignada.',
            'licencia_editorial_id.unique' => 'Este libro ya tiene esa licencia asignada.',
        ], [
            'libro_id' => 'libro',
            'licencia_editorial_id' => 'licencia editorial',
            'cantidad_lecturas' => 'cantidad de lecturas',
            'dias_prestamo' => 'días de préstamo',
            'precio_prestamo' => 'precio del préstamo',
        ])->validate();

        if (array_key_exists('cantidad_lecturas', $validados)) {
            $validados['cantidad_lecturas'] = $validados['cantidad_lecturas'] === '' || $validados['cantidad_lecturas'] === null
                ? null
                : (int) $validados['cantidad_lecturas'];
        }

        if (array_key_exists('dias_prestamo', $validados)) {
            $dias = $validados['dias_prestamo'];
            $validados['dias_prestamo'] = $dias === '' || $dias === null
                ? Licencia::DIAS_PRESTAMO_POR_DEFECTO
                : (int) $dias;
        }

        if (array_key_exists('precio_prestamo', $validados)) {
            $precio = $validados['precio_prestamo'];
            $validados['precio_prestamo'] = $precio === '' || $precio === null
                ? '0.00'
                : number_format((float) $precio, 2, '.', '');
        }

        return $validados;
    }
}

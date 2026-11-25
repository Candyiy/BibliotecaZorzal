<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Plan;
use App\Models\Promocion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PromocionController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $planPreseleccionado = $request->filled('plan')
            ? Plan::find($request->integer('plan'))
            : null;

        $consulta = $this->filtrar(
            Promocion::query(),
            $this->terminoDeBusqueda($request),
            ['motivo', 'estado'],
            ['planes' => ['tipo']],
        );

        return view('promociones.index', [
            'promociones' => $consulta->with('planes')->withCount('planes')->latest()->get(),
            'planes' => Plan::orderBy('precio')->get(),
            'planPreseleccionado' => $planPreseleccionado,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $planes = $datos['planes'];
        unset($datos['planes']);

        $promocion = Promocion::create($datos);
        $promocion->planes()->sync($planes);

        return redirect()->route('promociones.index')->with('status', 'Promoción agregada correctamente.');
    }

    public function update(Request $request, Promocion $promocion)
    {
        $datos = $this->validar($request);
        $planes = $datos['planes'];
        unset($datos['planes']);

        $promocion->update($datos);
        $promocion->planes()->sync($planes);

        return redirect()->route('promociones.index')->with('status', 'Promoción actualizada correctamente.');
    }

    public function destroy(Promocion $promocion)
    {
        $promocion->delete();

        return redirect()->route('promociones.index')->with('status', 'Promoción eliminada correctamente.');
    }

    private function validar(Request $request): array
    {
        return Validator::make($request->all(), [
            'motivo' => ['required', 'string', 'max:255'],
            'cuanto' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'f_inicio' => ['required', 'date'],
            'f_finalizacion' => ['required', 'date', 'after_or_equal:f_inicio'],
            'estado' => ['required', Rule::in(['activa', 'inactiva'])],
            'planes' => ['required', 'array', 'min:1'],
            'planes.*' => ['exists:planes,id'],
        ], [
            'motivo.required' => 'El campo motivo es obligatorio.',
            'motivo.max' => 'El motivo no debe superar los 255 caracteres.',
            'cuanto.required' => 'El campo descuento es obligatorio.',
            'cuanto.numeric' => 'El descuento debe ser un número.',
            'cuanto.min' => 'El descuento debe ser mayor que 0 %.',
            'cuanto.max' => 'El descuento no puede superar el 100 %.',
            'f_inicio.required' => 'La fecha de inicio es obligatoria.',
            'f_inicio.date' => 'La fecha de inicio no es válida.',
            'f_finalizacion.required' => 'La fecha de finalización es obligatoria.',
            'f_finalizacion.date' => 'La fecha de finalización no es válida.',
            'f_finalizacion.after_or_equal' => 'La fecha de finalización no puede ser anterior a la de inicio.',
            'estado.in' => 'Selecciona un estado válido.',
            'planes.required' => 'Selecciona al menos un plan.',
            'planes.min' => 'Selecciona al menos un plan.',
            'planes.*.exists' => 'Uno de los planes seleccionados no es válido.',
        ], [
            'motivo' => 'motivo',
            'cuanto' => 'descuento',
            'f_inicio' => 'fecha de inicio',
            'f_finalizacion' => 'fecha de finalización',
            'estado' => 'estado',
            'planes' => 'planes',
        ])->validate();
    }
}

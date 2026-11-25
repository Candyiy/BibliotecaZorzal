<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Libro;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $usuario = auth()->user();

        $consulta = $this->filtrar(
            Plan::query(),
            $this->terminoDeBusqueda($request),
            ['tipo', 'modalidad'],
            ['libro' => ['titulo']],
        );

        if (! $usuario->esStaff()) {
            $consulta->where('estado', Plan::ESTADO_ACTIVA);
        }

        return view('planes.index', [
            'planes' => $consulta->with(['promociones', 'libro', 'libros'])->withCount('suscripciones')
                ->orderBy('estado')
                ->orderBy('precio')
                ->get(),
            'libros' => Libro::orderBy('titulo')->get(['id', 'titulo']),
            'suscripcionActiva' => $usuario->suscripciones()
                ->with('plan')
                ->where('estado', 'activa')
                ->whereDate('f_finalizacion', '>=', today()->toDateString())
                ->latest('f_inicio')
                ->first(),
        ]);
    }

    public function store(Request $request)
    {
        [$datos, $libros] = $this->prepararDatos($request);

        DB::transaction(function () use ($datos, $libros): void {
            Plan::where('tipo', $datos['tipo'])
                ->where('estado', Plan::ESTADO_ACTIVA)
                ->update(['estado' => Plan::ESTADO_INACTIVA]);

            $plan = Plan::create($datos);

            if ($datos['modalidad'] === Plan::MODALIDAD_PAQUETE) {
                $plan->libros()->sync($libros ?? []);
            }
        });

        return redirect()->route('planes.index')->with('status', 'Plan agregado correctamente. El plan anterior del mismo tipo quedó inactivo.');
    }

    public function update(Request $request, Plan $plan)
    {
        [$datos, $libros] = $this->prepararDatos($request, $plan);
        $estado = $datos['estado'] ?? $plan->estado;

        DB::transaction(function () use ($plan, $datos, $libros, $estado): void {
            if ($estado === Plan::ESTADO_ACTIVA) {
                Plan::where('tipo', $datos['tipo'])
                    ->whereKeyNot($plan->id)
                    ->where('estado', Plan::ESTADO_ACTIVA)
                    ->update(['estado' => Plan::ESTADO_INACTIVA]);
            }

            $plan->update(array_merge($datos, ['estado' => $estado]));

            if ($datos['modalidad'] === Plan::MODALIDAD_PAQUETE) {
                if ($libros !== null) {
                    $plan->libros()->sync($libros);
                }
            } else {
                $plan->libros()->detach();
            }
        });

        return redirect()->route('planes.index')->with('status', 'Plan actualizado correctamente.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('planes.index')->with('status', 'Plan eliminado correctamente.');
    }

    public function suscribir(Request $request, Plan $plan)
    {
        abort_unless(
            $plan->estaActivo(),
            403,
            'Este plan ya no está disponible.'
        );

        $inicio = today();
        $esPrueba = $plan->tienePrueba();
        $fin = $esPrueba
            ? $inicio->copy()->addDays((int) $plan->trial_dias)
            : $inicio->copy()->addMonths($plan->duracionEnMeses());
        $monto = $esPrueba ? '0.00' : number_format($plan->precioFinal(), 2, '.', '');

        $suscripcion = DB::transaction(function () use ($request, $plan, $inicio, $fin, $esPrueba, $monto): Suscripcion {
            $request->user()->suscripciones()
                ->where('estado', 'activa')
                ->update(['estado' => 'cancelada']);

            $suscripcion = $request->user()->suscripciones()->create([
                'plan_id' => $plan->id,
                'f_inicio' => $inicio->toDateString(),
                'f_finalizacion' => $fin->toDateString(),
                'estado' => 'activa',
                'es_prueba' => $esPrueba,
            ]);

            $suscripcion->pagos()->create([
                'user_id' => $request->user()->id,
                'referencia' => 'PAG-'.$inicio->format('Ymd').'-'.Str::upper(Str::random(8)),
                'monto' => $monto,
                'metodo' => 'interno',
                'estado' => $esPrueba ? Pago::ESTADO_PRUEBA : Pago::ESTADO_PAGADO,
                'f_pago' => $inicio->toDateString(),
            ]);

            return $suscripcion;
        });

        return redirect()->route('planes.confirmacion', $suscripcion);
    }

    public function confirmacion(Suscripcion $suscripcion)
    {
        abort_unless($suscripcion->user_id === auth()->id(), 403);

        $suscripcion->load(['plan.libro', 'plan.libros', 'pagos']);

        abort_unless($suscripcion->plan, 404, 'El plan de esta suscripción ya no está disponible.');

        return view('planes.confirmacion', compact('suscripcion'));
    }

    public function cancelar(Request $request, Plan $plan)
    {
        $canceladas = $request->user()->suscripciones()
            ->where('plan_id', $plan->id)
            ->where('estado', 'activa')
            ->update(['estado' => 'cancelada']);

        $mensaje = $canceladas > 0
            ? 'Has quitado tu plan '.ucfirst($plan->tipo).'.'
            : 'No tienes un plan activo para quitar.';

        return redirect()->route('planes.index')->with('status', $mensaje);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, int>|null}
     */
    private function prepararDatos(Request $request, ?Plan $plan = null): array
    {
        $validados = $this->validar($request);

        $libros = array_key_exists('libros', $validados)
            ? array_values(array_unique(array_map('intval', $validados['libros'] ?? [])))
            : null;
        unset($validados['libros']);

        $beneficios = collect(explode("\n", (string) $request->input('beneficios', '')))
            ->map(fn ($beneficio): string => trim($beneficio))
            ->filter()
            ->take(10)
            ->values()
            ->all();
        unset($validados['beneficios']);

        $validados['beneficios'] = $beneficios;
        $validados['modalidad'] ??= $plan?->modalidad ?? Plan::MODALIDAD_GENERAL;

        if ($validados['modalidad'] !== Plan::MODALIDAD_LIBRO) {
            $validados['libro_id'] = null;
        }

        $validados['cantidad_libros'] = match ($validados['modalidad']) {
            Plan::MODALIDAD_LIBRO => 1,
            Plan::MODALIDAD_PAQUETE => count($libros ?? $plan?->libros()->pluck('libro_id')->all() ?? []),
            default => null,
        };

        return [$validados, $libros];
    }

    private function validar(Request $request): array
    {
        return Validator::make($request->all(), [
            'tipo' => ['required', Rule::in(['mensual', 'anual'])],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'estado' => ['sometimes', Rule::in([Plan::ESTADO_ACTIVA, Plan::ESTADO_INACTIVA])],
            'modalidad' => ['sometimes', Rule::in([
                Plan::MODALIDAD_GENERAL,
                Plan::MODALIDAD_LIBRO,
                Plan::MODALIDAD_PAQUETE,
            ])],
            'libro_id' => [
                'nullable',
                'integer',
                'required_if:modalidad,'.Plan::MODALIDAD_LIBRO,
                'exists:libros,id',
            ],
            'libros' => ['nullable', 'array', 'required_if:modalidad,'.Plan::MODALIDAD_PAQUETE],
            'libros.*' => ['integer', 'exists:libros,id'],
            'trial_dias' => ['nullable', 'integer', 'min:0', 'max:365'],
            'beneficios' => ['nullable', 'string', 'max:1000'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute no puede ser menor que :min.',
            'max' => 'El campo :attribute no puede superar :max.',
            'tipo.in' => 'Selecciona un tipo de plan válido.',
            'precio.max' => 'El precio no puede superar los 99999999.99.',
            'estado.in' => 'Selecciona un estado válido.',
            'modalidad.in' => 'Selecciona una cobertura válida.',
            'libro_id.required_if' => 'Selecciona el libro del plan.',
            'libro_id.exists' => 'El libro seleccionado no es válido.',
            'libros.required_if' => 'Selecciona al menos un libro para el paquete.',
            'libros.*.exists' => 'Uno de los libros seleccionados no es válido.',
            'trial_dias.max' => 'La prueba no puede superar los 365 días.',
            'beneficios.max' => 'Los beneficios no pueden superar los 1000 caracteres.',
        ], [
            'tipo' => 'tipo',
            'precio' => 'precio',
            'estado' => 'estado',
            'modalidad' => 'cobertura',
            'libro_id' => 'libro',
            'libros' => 'libros',
            'trial_dias' => 'días de prueba',
            'beneficios' => 'beneficios',
        ])->validate();
    }
}

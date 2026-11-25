<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\User;
use App\Notifications\PrestamoRenovado;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class PrestamoController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $usuario = $request->user();
        $termino = $this->terminoDeBusqueda($request);
        $estado = (string) $request->query('estado', '');
        $orden = $request->query('orden') === 'desc' ? 'desc' : 'asc';

        if (! in_array($estado, [Prestamo::ESTADO_ACTIVO, Prestamo::ESTADO_DEVUELTO, 'vencido'], true)) {
            $estado = '';
        }

        $consulta = Prestamo::query();

        if (! $usuario->esStaff()) {
            $consulta->where('user_id', $usuario->id);
        }

        if ($estado === 'vencido') {
            $consulta->vencidos();
        } elseif ($estado !== '') {
            $consulta->where('estado', $estado);
        }

        $this->filtrar(
            $consulta,
            $termino,
            [],
            ['licencia.libro' => ['titulo', 'descripcion'], 'usuario' => ['name', 'email']],
        );

        $prestamos = $consulta->with(['usuario', 'licencia.libro'])
            ->orderBy('f_inicio', $orden)
            ->orderBy('id', $orden)
            ->paginate(12)
            ->withQueryString();

        $opcionesFiltros = [
            [
                'name' => 'estado',
                'label' => 'Estado',
                'placeholder' => 'Todos los estados',
                'options' => [
                    Prestamo::ESTADO_ACTIVO => 'Activo',
                    'vencido' => 'Vencido',
                    Prestamo::ESTADO_DEVUELTO => 'Devuelto',
                ],
            ],
            [
                'name' => 'orden',
                'label' => 'Orden cronológico',
                'placeholder' => 'Más antiguos primero',
                'options' => [
                    'asc' => 'Más antiguos primero',
                    'desc' => 'Más recientes primero',
                ],
            ],
        ];

        return view('prestamos.index', compact('prestamos', 'opcionesFiltros', 'estado', 'orden'));
    }

    public function store(Request $request, Libro $libro)
    {
        $usuario = $request->user();

        abort_unless(
            $usuario->esStaff() || $usuario->suscripcionActiva()->exists(),
            403,
            'Necesitas una suscripción activa para solicitar préstamos.'
        );

        $this->asegurarDisponibilidad($usuario);

        $licencia = $libro->licencias()->first();

        abort_unless($licencia, 404, 'Este libro no tiene una licencia disponible.');

        abort_if(
            $usuario->prestamos()->activos()->where('licencia_id', $licencia->id)->exists(),
            403,
            'Ya tienes un préstamo activo de este libro.'
        );

        $inicio = today();

        Prestamo::create([
            'cantidad' => 1,
            'f_inicio' => $inicio->toDateString(),
            'f_finalizacion' => $inicio->copy()->addDays(Prestamo::DIAS_PLAZO)->toDateString(),
            'estado' => Prestamo::ESTADO_ACTIVO,
            'user_id' => $usuario->id,
            'licencia_id' => $licencia->id,
        ]);

        return redirect()->route('prestamos.index')
            ->with('status', 'Préstamo solicitado correctamente. Devuélvelo en '.Prestamo::DIAS_PLAZO.' días.');
    }

    public function devolver(Request $request, Prestamo $prestamo)
    {
        $usuario = $request->user();

        abort_unless($usuario->esStaff() || $prestamo->user_id === $usuario->id, 403);
        abort_unless($prestamo->estado === Prestamo::ESTADO_ACTIVO, 422, 'Este préstamo ya fue devuelto.');

        $prestamo->devolver();

        $mensaje = $prestamo->dias_penalizacion > 0
            ? 'Devolución registrada. Penalización de '.$prestamo->dias_penalizacion.' '.($prestamo->dias_penalizacion === 1 ? 'día' : 'días').'.'
            : 'Devolución registrada correctamente.';

        return redirect()->route('prestamos.index')->with('status', $mensaje);
    }

    public function renovar(Request $request, Prestamo $prestamo)
    {
        $usuario = $request->user();

        abort_unless($usuario->esStaff() || $prestamo->user_id === $usuario->id, 403);
        abort_unless($prestamo->puedeRenovarse(), 422, 'Este préstamo ya no puede renovarse.');

        $prestamo->renovar();

        $usuario = $prestamo->usuario;

        if ($usuario) {
            $clave = 'renovada-'.$prestamo->id.'-'.$prestamo->renovaciones.'-'.today()->toDateString();

            $yaNotificado = $usuario->notifications()
                ->where('type', PrestamoRenovado::class)
                ->where('data->clave', $clave)
                ->exists();

            if (! $yaNotificado) {
                $usuario->notify(new PrestamoRenovado($prestamo, $clave));
            }
        }

        return redirect()->route('prestamos.index')->with(
            'status',
            'Préstamo renovado. La nueva fecha de devolución es '.$prestamo->f_finalizacion->format('d/m/Y').'.'
        );
    }

    private function asegurarDisponibilidad(User $usuario): void
    {
        abort_if(
            $usuario->prestamos()->vencidos()->exists(),
            403,
            'Tienes préstamos vencidos. Devuélvelos antes de solicitar otro.'
        );

        $bloqueos = $usuario->prestamos()
            ->where('dias_penalizacion', '>', 0)
            ->whereNotNull('f_devolucion')
            ->get()
            ->map(fn (Prestamo $prestamo): CarbonInterface => $prestamo->f_devolucion->copy()->addDays($prestamo->dias_penalizacion))
            ->filter(fn (CarbonInterface $fecha): bool => $fecha->gt(today()));

        if ($bloqueos->isNotEmpty()) {
            $hasta = $bloqueos->sortDesc()->first();

            abort_if(
                $hasta->gt(today()),
                403,
                'No puedes solicitar préstamos hasta el '.$hasta->format('d/m/Y').'.'
            );
        }

        abort_if(
            $usuario->prestamos()->activos()->count() >= Prestamo::MAX_ACTIVOS,
            403,
            'Ya tienes el máximo de '.Prestamo::MAX_ACTIVOS.' préstamos activos.'
        );
    }
}

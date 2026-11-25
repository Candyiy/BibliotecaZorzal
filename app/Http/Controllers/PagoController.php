<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltraPorBusqueda;
use App\Models\Pago;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    use FiltraPorBusqueda;

    public function index(Request $request)
    {
        $usuario = $request->user();

        $consulta = Pago::query();

        if (! $usuario->esStaff()) {
            $consulta->where('user_id', $usuario->id);
        }

        $this->filtrar(
            $consulta,
            $this->terminoDeBusqueda($request),
            ['referencia', 'estado'],
            ['usuario' => ['name', 'email'], 'suscripcion.plan' => ['tipo'], 'prestamo.licencia.libro' => ['titulo']],
        );

        $pagos = $consulta->with(['usuario', 'suscripcion.plan', 'prestamo.licencia.libro'])
            ->latest('f_pago')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('pagos.index', compact('pagos'));
    }

    public function show(Pago $pago)
    {
        $usuario = auth()->user();

        abort_unless($usuario->esStaff() || $pago->user_id === $usuario->id, 403);

        $pago->load(['usuario', 'suscripcion.plan.libro', 'suscripcion.plan.libros', 'prestamo.licencia.libro']);

        return view('pagos.show', compact('pago'));
    }
}

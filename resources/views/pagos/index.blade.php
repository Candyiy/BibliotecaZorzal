<x-layouts::app :title="__('Pagos')">
    @php $esStaff = auth()->user()->esStaff(); @endphp

    <div class="z-page">
        <x-z.page-header title="Pagos" subtitle="Historial de pagos registrados por las suscripciones." />

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por referencia, usuario o plan..." :resultados="$pagos->total()" />

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Referencia</th>
                        @if ($esStaff)
                            <th scope="col">Usuario</th>
                        @endif
                        <th scope="col">Plan</th>
                        <th scope="col">Monto</th>
                        <th scope="col">Método</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pagos as $pago)
                        <tr>
                            <td>
                                <a href="{{ route('pagos.show', $pago) }}" wire:navigate class="cell-primary" aria-label="Ver detalle de {{ $pago->referencia }}">
                                    {{ $pago->referencia }}
                                </a>
                                <span class="cell-sub">Ver detalle</span>
                            </td>
                            @if ($esStaff)
                                <td>
                                    <span class="cell-primary">{{ $pago->usuario?->name ?? 'Usuario eliminado' }}</span>
                                    <span class="cell-sub">{{ $pago->usuario?->email }}</span>
                                </td>
                            @endif
                            <td>
                                @if ($pago->suscripcion?->plan)
                                    Plan {{ ucfirst($pago->suscripcion->plan->tipo) }}
                                @else
                                    <span class="z-hint">Plan eliminado</span>
                                @endif
                            </td>
                            <td class="tabular-nums">{{ number_format((float) $pago->monto, 2, ',', '.') }}</td>
                            <td>{{ ucfirst($pago->metodo) }}</td>
                            <td>
                                <x-z.badge variant="{{ $pago->estado === \App\Models\Pago::ESTADO_PAGADO ? 'success' : 'warning' }}">
                                    {{ ucfirst($pago->estado) }}
                                </x-z.badge>
                            </td>
                            <td>{{ $pago->f_pago?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $esStaff ? 7 : 6 }}">
                                <x-z.vacio titulo="Todavía no hay pagos." copy="Cuando te suscribas a un plan, aparecerá aquí." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $pagos->links() }}
    </div>
</x-layouts::app>

<x-layouts::app :title="__('Préstamos')">
    @php $esStaff = auth()->user()->esStaff(); @endphp

    <div class="z-page">
        <x-z.page-header
            title="Préstamos"
            subtitle="Solicita, controla y devuelve tus préstamos digitales. Orden: {{ $orden === 'desc' ? 'más recientes primero' : 'más antiguos primero' }}."
        >
            <x-slot name="actions">
                <a href="{{ route('libros.index') }}" wire:navigate class="btn btn-primary">
                    <x-z.icon name="book-open" class="size-4" />
                    Explorar catálogo
                </a>
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por libro, autor o usuario..." :resultados="$prestamos->total()" :filtros="$opcionesFiltros" />

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Libro</th>
                        @if ($esStaff)
                            <th scope="col">Usuario</th>
                        @endif
                        <th scope="col">Inicio</th>
                        <th scope="col">Vencimiento</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prestamos as $prestamo)
                        @php
                            $libroPrestado = $prestamo->licencia?->libro;
                            $puedeLeerPrestamo = $libroPrestado
                                && $libroPrestado->archivo_pdf
                                && $prestamo->estado === \App\Models\Prestamo::ESTADO_ACTIVO
                                && ! $prestamo->estaVencido()
                                && ($esStaff || $prestamo->user_id === auth()->id())
                                && ($esStaff || ! auth()->user()->tieneAccesoLimitadoPorPrestamos());
                        @endphp
                        <tr>
                            <td>
                                <span class="cell-primary">{{ $prestamo->licencia?->libro?->titulo ?? 'Libro eliminado' }}</span>
                                <span class="cell-sub">{{ $prestamo->licencia?->libro?->autores->pluck('nombre')->join(', ') ?: 'Sin autor' }}</span>
                            </td>
                            @if ($esStaff)
                                <td>
                                    <span class="cell-primary">{{ $prestamo->usuario?->name ?? 'Usuario eliminado' }}</span>
                                    <span class="cell-sub">{{ $prestamo->usuario?->email }}</span>
                                </td>
                            @endif
                            <td>{{ $prestamo->f_inicio?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                {{ $prestamo->f_finalizacion?->format('d/m/Y') ?? '—' }}
                                @if ($prestamo->esConContador())
                                    <span class="cell-sub">Préstamo de {{ $prestamo->licencia?->diasPrestamo() ?? \App\Models\Licencia::DIAS_PRESTAMO_POR_DEFECTO }} días · sin renovación</span>
                                @endif
                                @if ($prestamo->pago)
                                    <span class="cell-sub tabular-nums">Pagado: {{ number_format((float) $prestamo->pago->monto, 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($prestamo->estaVencido())
                                    <x-z.badge variant="danger">Vencido</x-z.badge>
                                @elseif ($prestamo->estado === \App\Models\Prestamo::ESTADO_DEVUELTO)
                                    <x-z.badge variant="neutral">Devuelto</x-z.badge>
                                @else
                                    <x-z.badge variant="success">Activo</x-z.badge>
                                @endif
                                @if ($prestamo->dias_penalizacion > 0)
                                    <span class="cell-sub">{{ $prestamo->dias_penalizacion }} {{ $prestamo->dias_penalizacion === 1 ? 'día' : 'días' }} de penalización</span>
                                @endif
                                @if ($prestamo->renovaciones > 0)
                                    <span class="cell-sub">{{ $prestamo->renovaciones }} {{ $prestamo->renovaciones === 1 ? 'renovación' : 'renovaciones' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="z-row-actions">
                                    @if ($puedeLeerPrestamo)
                                        <a href="{{ route('libros.leer', $libroPrestado) }}?v={{ md5($libroPrestado->archivo_pdf ?? '') }}"
                                           class="btn btn-secondary btn-sm"
                                           aria-label="Leer {{ $libroPrestado->titulo }}">
                                            <x-z.icon name="eye" class="size-4" />
                                            Leer
                                        </a>
                                    @endif
                                    @if ($prestamo->puedeRenovarse() && ($esStaff || $prestamo->user_id === auth()->id()))
                                        <form method="POST" action="{{ route('prestamos.renovar', $prestamo) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">
                                                <x-z.icon name="calendar" class="size-4" />
                                                Renovar
                                            </button>
                                        </form>
                                    @endif
                                    @if ($prestamo->estado === \App\Models\Prestamo::ESTADO_ACTIVO && ($esStaff || $prestamo->user_id === auth()->id()))
                                        <form method="POST" action="{{ route('prestamos.devolver', $prestamo) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <x-z.icon name="rotate-ccw" class="size-4" />
                                                Devolver
                                            </button>
                                        </form>
                                    @else
                                        <span class="z-hint">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $esStaff ? 6 : 5 }}">
                                <x-z.vacio titulo="Todavía no hay préstamos." copy="Cuando solicites un libro, aparecerá aquí." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $prestamos->links() }}
    </div>
</x-layouts::app>

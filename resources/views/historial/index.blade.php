<x-layouts::app :title="__('Historial')">
    <div class="z-page">
        <x-z.page-header title="Historial de lectura" subtitle="Los libros que has abierto, ordenados por última lectura.">
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

        <p class="z-hint mb-4">
            Has abierto {{ $totalLibros }} {{ $totalLibros === 1 ? 'libro' : 'libros' }} hasta ahora.
        </p>

        <x-z.buscador placeholder="Buscar por título, descripción o autor..." :resultados="$lecturas->total()" />

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Libro</th>
                        <th scope="col">Autor</th>
                        <th scope="col">Calificación</th>
                        <th scope="col">Última lectura</th>
                        <th scope="col">Primera lectura</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturas as $lectura)
                        @php $libro = $lectura->libro; @endphp
                        <tr>
                            <td>
                                <span class="cell-primary">{{ $libro?->titulo ?? 'Libro eliminado' }}</span>
                                <span class="cell-sub">{{ \Illuminate\Support\Str::limit($libro?->descripcion ?? '', 90) }}</span>
                            </td>
                            <td>
                                {{ $libro && $libro->autores->isNotEmpty() ? $libro->autores->pluck('nombre')->join(', ') : 'Sin autor' }}
                            </td>
                            <td>
                                @if ($libro && $libro->resenas_count > 0)
                                    <span class="cell-primary tabular-nums">{{ number_format((float) $libro->resenas_avg_puntaje, 1, ',', '.') }} / 5 · {{ $libro->porcentajeResenas() }}%</span>
                                    <span class="cell-sub">{{ $libro->resenas_count }} {{ $libro->resenas_count === 1 ? 'reseña' : 'reseñas' }}</span>
                                @else
                                    <span class="cell-sub">Sin reseñas</span>
                                @endif
                            </td>
                            <td>{{ $lectura->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $lectura->created_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <div class="z-row-actions">
                                    @if ($libro?->archivo_pdf)
                                        <a href="{{ route('libros.leer', $libro) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" aria-label="Leer {{ $libro->titulo }}">
                                            <x-z.icon name="eye" class="size-4" />
                                            Leer
                                        </a>
                                    @endif
                                    <a href="{{ route('libros.index') }}" wire:navigate class="btn btn-ghost btn-sm btn-icon" aria-label="Ver en el catálogo">
                                        <x-z.icon name="book-open" class="size-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-z.vacio titulo="Todavía no hay lecturas." copy="Abre un libro desde el catálogo y aparecerá aquí." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $lecturas->links() }}
    </div>
</x-layouts::app>

<x-layouts::app :title="__('Notificaciones')">
    <div class="z-page">
        <x-z.page-header title="Notificaciones" subtitle="Avisos de préstamos por vencer, vencidos y de renovación de planes.">
            @if ($sinLeer > 0)
                <x-slot name="actions">
                    <form method="POST" action="{{ route('notificaciones.marcar-leidas') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            <x-z.icon name="check" class="size-4" />
                            Marcar todas como leídas
                        </button>
                    </form>
                </x-slot>
            @endif
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Aviso</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notificaciones as $notificacion)
                        <tr>
                            <td>
                                <span class="cell-primary">{{ $notificacion->data['titulo'] ?? 'Notificación' }}</span>
                                <span class="cell-sub">{{ $notificacion->data['mensaje'] ?? '' }}</span>
                                @if (! empty($notificacion->data['tipo']))
                                    @php
                                        $variante = match ($notificacion->data['tipo']) {
                                            'vencido' => 'danger',
                                            'renovacion' => 'accent',
                                            'renovado' => 'success',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <x-z.badge variant="{{ $variante }}">
                                        {{ match ($notificacion->data['tipo']) {
                                            'vencido' => 'Vencido',
                                            'por_vencer' => 'Por vencer',
                                            'renovacion' => 'Renovación',
                                            'renovado' => 'Renovado',
                                            default => 'Aviso',
                                        } }}
                                    </x-z.badge>
                                @endif
                                @if (! empty($notificacion->data['url']))
                                    <a href="{{ $notificacion->data['url'] }}" wire:navigate class="cell-sub">
                                        {{ ($notificacion->data['tipo'] ?? '') === 'renovacion' ? 'Ver planes' : 'Ver préstamos' }}
                                    </a>
                                @endif
                            </td>
                            <td>{{ $notificacion->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($notificacion->read_at)
                                    <x-z.badge variant="neutral">Leída</x-z.badge>
                                @else
                                    <x-z.badge variant="warning">Sin leer</x-z.badge>
                                @endif
                            </td>
                            <td>
                                <div class="z-row-actions">
                                    @if (! $notificacion->read_at)
                                        <form method="POST" action="{{ route('notificaciones.marcar-leida', $notificacion) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">
                                                <x-z.icon name="check" class="size-4" />
                                                Marcar leída
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
                            <td colspan="4">
                                <x-z.vacio titulo="No tienes notificaciones." copy="Te avisaremos cuando un préstamo esté por vencer." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $notificaciones->links() }}
    </div>
</x-layouts::app>

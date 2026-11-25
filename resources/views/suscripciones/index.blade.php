<x-layouts::app :title="__('Mis suscripciones')">
    <div class="z-page">
        <x-z.page-header title="Mis suscripciones" subtitle="Historial de los planes que has contratado.">
            <x-slot name="actions">
                <a href="{{ route('planes.index') }}" wire:navigate class="btn btn-primary">
                    <x-z.icon name="layers" class="size-4" />
                    Ver planes
                </a>
            </x-slot>
        </x-z.page-header>

        <x-z.buscador placeholder="Buscar por plan, tipo o estado..." :resultados="$suscripciones->total()" />

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Plan</th>
                        <th scope="col">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suscripciones as $suscripcion)
                        <tr>
                            <td>
                                <span class="cell-primary">
                                    {{ $suscripcion->plan ? 'Plan '.ucfirst($suscripcion->plan->tipo) : 'Plan eliminado' }}
                                </span>
                            </td>
                            <td>{{ $suscripcion->plan ? ucfirst($suscripcion->plan->tipo) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <x-z.vacio titulo="Todavía no tienes suscripciones." copy="Elige un plan y aparecerá aquí." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $suscripciones->links() }}
    </div>
</x-layouts::app>

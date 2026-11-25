<x-layouts::app :title="__('Detalle del pago')">
    <div class="z-page">
        <x-z.page-header title="Detalle del pago" subtitle="Información completa de la transacción registrada.">
            <x-slot name="actions">
                <a href="{{ route('pagos.index') }}" wire:navigate class="btn btn-secondary">
                    <x-z.icon name="arrow-right" class="size-4" />
                    Volver al historial
                </a>
            </x-slot>
        </x-z.page-header>

        <div class="z-card max-w-2xl">
            <p class="z-card-title">{{ $pago->referencia }}</p>
            <p class="z-card-note">Pago {{ ucfirst($pago->metodo) }} registrado el {{ $pago->f_pago?->format('d/m/Y') ?? '—' }}.</p>

            <dl class="mt-5 grid gap-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Estado</dt>
                    <dd>
                        <x-z.badge variant="{{ $pago->estado === \App\Models\Pago::ESTADO_PAGADO ? 'success' : 'warning' }}">
                            {{ ucfirst($pago->estado) }}
                        </x-z.badge>
                    </dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Monto</dt>
                    <dd class="text-sm font-medium text-ink tabular-nums">{{ number_format((float) $pago->monto, 2, ',', '.') }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Método</dt>
                    <dd class="text-sm font-medium text-ink">{{ ucfirst($pago->metodo) }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Usuario</dt>
                    <dd class="text-sm font-medium text-ink">{{ $pago->usuario?->name ?? 'Usuario eliminado' }}</dd>
                    <dd class="cell-sub">{{ $pago->usuario?->email }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Plan</dt>
                    <dd class="text-sm font-medium text-ink">
                        @if ($pago->suscripcion?->plan)
                            Plan {{ ucfirst($pago->suscripcion->plan->tipo) }}
                            <span class="cell-sub">{{ $pago->suscripcion->plan->modalidadEtiqueta() }} · {{ $pago->suscripcion->plan->coberturaEtiqueta() }}</span>
                        @else
                            <span class="z-hint">Plan eliminado</span>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Vigencia de la suscripción</dt>
                    <dd class="text-sm font-medium text-ink">
                        {{ $pago->suscripcion?->f_inicio?->format('d/m/Y') ?? '—' }} —
                        {{ $pago->suscripcion?->f_finalizacion?->format('d/m/Y') ?? '—' }}
                        @if ($pago->suscripcion?->esPrueba())
                            <x-z.badge variant="accent">Prueba</x-z.badge>
                        @endif
                    </dd>
                </div>
                @if ($pago->suscripcion?->plan)
                    @foreach ($pago->suscripcion->plan->beneficiosLista() as $beneficio)
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <dt class="field-label">Funcionalidad</dt>
                            <dd class="text-sm font-medium text-ink">{{ $beneficio }}</dd>
                        </div>
                    @endforeach
                @endif
            </dl>

            <div class="form-actions mt-6">
                <a href="{{ route('planes.index') }}" wire:navigate class="btn btn-primary">Ver planes</a>
            </div>
        </div>
    </div>
</x-layouts::app>

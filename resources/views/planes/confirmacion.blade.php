<x-layouts::app :title="__('Suscripción confirmada')">
    <div class="z-page">
        <x-z.page-header
            title="Suscripción confirmada"
            subtitle="Tu plan ya está listo para usar."
        />

        <div class="z-card max-w-xl">
            <p class="z-card-title">Plan {{ ucfirst($suscripcion->plan->tipo) }}</p>
            <p class="z-card-note">Estos son los datos de tu suscripción.</p>

            <dl class="mt-5 grid gap-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Plan</dt>
                    <dd class="text-sm font-medium text-ink">{{ ucfirst($suscripcion->plan->tipo) }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Tipo</dt>
                    <dd class="text-sm font-medium text-ink">{{ ucfirst($suscripcion->plan->tipo) }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Cobertura</dt>
                    <dd class="text-sm font-medium text-ink">
                        {{ $suscripcion->plan->modalidadEtiqueta() }} · {{ $suscripcion->plan->coberturaEtiqueta() }}
                    </dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Vigencia</dt>
                    <dd class="text-sm font-medium text-ink">
                        {{ $suscripcion->f_inicio?->format('d/m/Y') }} — {{ $suscripcion->f_finalizacion?->format('d/m/Y') }}
                        @if ($suscripcion->esPrueba())
                            <x-z.badge variant="accent">Período de prueba</x-z.badge>
                        @endif
                    </dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <dt class="field-label">Funcionalidades</dt>
                    <dd class="text-sm font-medium text-ink">
                        @forelse ($suscripcion->plan->beneficiosLista() as $beneficio)
                            <span class="cell-sub">{{ $beneficio }}</span>
                        @empty
                            <span class="z-hint">Sin beneficios listados.</span>
                        @endforelse
                    </dd>
                </div>
                @if ($suscripcion->pagos->first())
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <dt class="field-label">Pago registrado</dt>
                        <dd class="text-sm font-medium text-ink">
                            {{ $suscripcion->pagos->first()->referencia }} ·
                            {{ number_format((float) $suscripcion->pagos->first()->monto, 2, ',', '.') }} ·
                            {{ ucfirst($suscripcion->pagos->first()->estado) }}
                        </dd>
                    </div>
                @endif
            </dl>

            <div class="form-actions mt-6">
                <a href="{{ route('planes.index') }}" class="btn btn-primary">Ver planes</a>
                <a href="{{ route('libros.index') }}" class="btn btn-secondary">Explorar libros</a>
            </div>
        </div>
    </div>
</x-layouts::app>

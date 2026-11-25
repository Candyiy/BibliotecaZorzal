<x-layouts::app :title="__('Reportes')">
    @php
        $filtros = [
            [
                'name' => 'mes',
                'label' => 'Mes',
                'placeholder' => 'Todos los meses',
                'options' => $meses->all(),
            ],
        ];
    @endphp

    <div class="z-page">
        <x-z.page-header
            title="Reportes de gestión"
            subtitle="Descarga la información de la biblioteca en CSV o PDF, con filtro mensual opcional."
        />

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por reporte..." :resultados="$reportes->count()" :filtros="$filtros" />

        @if ($reportes->isEmpty())
            <div class="z-card">
                <p class="z-card-title">Sin reportes para esa búsqueda</p>
                <p class="z-card-note">Prueba con otro término o limpia los filtros.</p>
            </div>
        @else
            <div class="z-records">
                @foreach ($reportes as $reporte)
                    @php
                        $base = ['tipo' => $reporte['clave'], 'mes' => $mes];
                    @endphp
                    <article class="z-record">
                        <div class="z-record-top">
                            <x-z.badge variant="accent">
                                <x-z.icon name="{{ $reporte['icono'] }}" class="size-3.5" />
                                PDF
                            </x-z.badge>
                            <x-z.badge variant="success">CSV</x-z.badge>
                        </div>

                        <h2 class="z-record-title">{{ $reporte['titulo'] }}</h2>
                        <p class="z-record-sub line-clamp-2">{{ $reporte['copy'] }}</p>

                        <div class="z-record-actions">
                            <a href="{{ route('reportes.exportar', $base + ['formato' => 'pdf']) }}" class="btn btn-primary btn-sm">
                                <x-z.icon name="download" class="size-4" />
                                Descargar PDF
                            </a>
                            <a href="{{ route('reportes.exportar', $base + ['formato' => 'csv']) }}" class="btn btn-secondary btn-sm">
                                <x-z.icon name="download" class="size-4" />
                                Descargar CSV
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>

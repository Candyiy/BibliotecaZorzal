@props([
    'placeholder' => 'Buscar...',
    'resultados' => null,
    'filtros' => [],
])

@php
    $termino = trim((string) request()->query('q', ''));
    $nombresFiltros = collect($filtros)->pluck('name')->all();
    $consulta = collect(request()->except(array_merge(['q', 'page'], $nombresFiltros)))
        ->filter(fn ($valor) => is_scalar($valor) && $valor !== '')
        ->all();
    $hayFiltros = collect($filtros)->contains(
        fn ($filtro) => trim((string) request()->query($filtro['name'], '')) !== ''
    );
    $activo = $termino !== '' || $hayFiltros;
@endphp

<form method="GET" action="{{ request()->url() }}" role="search" class="z-search">
    @foreach ($consulta as $clave => $valor)
        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
    @endforeach

    <div class="relative w-full max-w-md">
        <label for="buscador-q" class="sr-only">Buscar</label>
        <x-z.icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-soft" />
        <input id="buscador-q" type="search" name="q" value="{{ $termino }}" placeholder="{{ $placeholder }}" class="field ps-9">
    </div>

    @foreach ($filtros as $filtro)
        <div>
            <label for="filtro-{{ $filtro['name'] }}" class="sr-only">{{ $filtro['label'] }}</label>
            <select id="filtro-{{ $filtro['name'] }}" name="{{ $filtro['name'] }}" class="field" aria-label="{{ $filtro['label'] }}">
                <option value="">{{ $filtro['placeholder'] ?? 'Todos' }}</option>
                @foreach ($filtro['options'] as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected((string) request()->query($filtro['name'], '') === (string) $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
    @endforeach

    <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>

    @if ($activo)
        <a href="{{ request()->url() }}" wire:navigate class="btn btn-ghost btn-sm">Limpiar</a>
    @endif
</form>

@if ($activo)
    <p class="z-hint mb-4">
        @if ($resultados === null)
            @if ($termino !== '')
                Resultados para «{{ $termino }}».
            @else
                Resultados aplicando los filtros seleccionados.
            @endif
        @else
            @if ($termino !== '')
                {{ $resultados }} {{ $resultados === 1 ? 'resultado' : 'resultados' }} para «{{ $termino }}».
            @else
                {{ $resultados }} {{ $resultados === 1 ? 'resultado' : 'resultados' }}.
            @endif
        @endif
    </p>
@endif

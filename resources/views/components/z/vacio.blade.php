@props([
    'titulo',
    'copy' => null,
])

@php $buscando = trim((string) request()->query('q', '')) !== ''; @endphp

<div class="soft-empty">
    <p class="empty-title">{{ $buscando ? 'No se encontraron resultados.' : $titulo }}</p>
    @if ($copy)
        <p class="mt-1 text-sm">{{ $buscando ? 'Prueba con otro término de búsqueda.' : $copy }}</p>
    @endif
</div>

@php
    $vencida = $editorial->f_finalizacion?->lt(now()->startOfDay()) ?? false;
    $proxima = $editorial->f_inicio?->gt(now()->startOfDay()) ?? false;
@endphp

<x-z.badge variant="{{ $vencida ? 'danger' : ($proxima ? 'warning' : 'success') }}">
    {{ $vencida ? 'Vencida' : ($proxima ? 'Próxima' : 'Vigente') }}
</x-z.badge>

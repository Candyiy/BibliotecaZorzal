@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'z-badge',
        'success' => 'z-badge z-badge-success',
        'warning' => 'z-badge z-badge-warning',
        'danger' => 'z-badge z-badge-danger',
        'info' => 'z-badge z-badge-info',
        'accent' => 'z-badge z-badge-accent',
    ];
@endphp

<span {{ $attributes->merge(['class' => $variants[$variant] ?? $variants['neutral']]) }}>
    {{ $slot }}
</span>

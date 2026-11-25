@props([
    'title',
    'subtitle' => null,
])

<div class="z-page-header">
    <div>
        <h1 class="z-page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="z-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="z-page-actions">{{ $actions }}</div>
    @endisset
</div>

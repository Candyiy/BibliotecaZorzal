@props([
    'sidebar' => false,
])

<a {{ $attributes->merge(['class' => 'flex items-center gap-3 no-underline']) }} href="{{ route('libros.index') }}" wire:navigate>
    <span class="z-brand-mark">
        <x-z.icon name="book-open" class="size-4" />
    </span>
    <span class="z-wordmark">
        ZORZEL
        @if ($sidebar)
            <span class="z-wordmark-caption">Biblioteca digital</span>
        @endif
    </span>
</a>

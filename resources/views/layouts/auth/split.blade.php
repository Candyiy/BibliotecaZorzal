<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="hidden flex-col justify-between border-e border-line bg-sidebar p-10 lg:flex">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 no-underline">
                    <span class="z-brand-mark">
                        <x-z.icon name="book-open" class="size-4" />
                    </span>
                    <span class="z-wordmark">ZORZEL<span class="z-wordmark-caption">Biblioteca digital</span></span>
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <blockquote class="max-w-md">
                    <p class="font-display text-3xl leading-tight text-ink">&ldquo;{{ trim($message) }}&rdquo;</p>
                    <footer class="mt-4 text-sm text-ink-soft">{{ trim($author) }}</footer>
                </blockquote>
            </div>

            <div class="flex items-center justify-center px-5 py-10">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

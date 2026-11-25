<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <div class="z-auth-shell">
            <div class="w-full max-w-sm">
                <a href="{{ route('home') }}" wire:navigate class="z-auth-brand no-underline">
                    <span class="z-brand-mark size-10">
                        <x-z.icon name="book-open" class="size-5" />
                    </span>
                    <span>
                        <span class="z-wordmark text-2xl">ZORZEL</span>
                        <span class="z-wordmark-caption">Biblioteca digital</span>
                    </span>
                </a>

                <div class="z-auth-card">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-ink-soft">
                    Lectura, conocimiento y discreción.
                </p>
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

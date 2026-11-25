<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="app-body min-h-screen bg-canvas text-ink">
        @php
            $section = match (true) {
                request()->routeIs('libros.*') => 'Libros',
                request()->routeIs('autores.*') => 'Autores',
                request()->routeIs('licencias*') => 'Licencias',
                request()->routeIs('historial.*') => 'Historial',
                request()->routeIs('prestamos.*') => 'Préstamos',
                request()->routeIs('favoritos.*') => 'Favoritos',
                request()->routeIs('pagos.*') => 'Pagos',
                request()->routeIs('notificaciones.*') => 'Notificaciones',
                request()->routeIs('reportes.*') => 'Reportes',
                request()->routeIs('promociones.*') => 'Promociones',
                request()->routeIs('usuarios.*') => 'Usuarios',
                request()->routeIs('planes.*') => 'Planes',
                request()->routeIs('suscripciones.*') => 'Suscripciones',
                request()->routeIs('profile.edit', 'security.edit', 'appearance.edit') => 'Ajustes',
                default => 'ZORZEL',
            };
        @endphp

        <div x-data="{ open: false }" class="z-shell">
            <div
                x-cloak
                x-show="open"
                @click="open = false"
                class="fixed inset-0 z-40 bg-black/70 lg:hidden"
                aria-hidden="true"
            ></div>

            <aside class="z-sidebar" :class="open ? 'is-open' : ''" aria-label="Navegación principal">
                <div class="z-sidebar-brand">
                    <a href="{{ route('libros.index') }}" wire:navigate class="flex min-w-0 items-center gap-3 no-underline">
                        <span class="z-brand-mark">
                            <x-z.icon name="book-open" class="size-4" />
                        </span>
                        <span class="z-wordmark">
                            ZORZEL
                            <span class="z-wordmark-caption">Biblioteca digital</span>
                        </span>
                    </a>
                    <button type="button" class="z-icon-button z-mobile-only ms-auto" @click="open = false" aria-label="Cerrar menú">
                        <x-z.icon name="x" class="size-4" />
                    </button>
                </div>

                <nav class="z-sidebar-section" aria-label="Colección">
                    <p class="z-sidebar-label">Colección</p>

                    <a href="{{ route('libros.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('libros.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('libros.*')) aria-current="page" @endif>
                        <x-z.icon name="book-open" class="size-[18px] shrink-0" />
                        <span>Libros</span>
                        @if (request()->routeIs('libros.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('autores.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('autores.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('autores.*')) aria-current="page" @endif>
                        <x-z.icon name="users" class="size-[18px] shrink-0" />
                        <span>Autores</span>
                        @if (request()->routeIs('autores.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    @if (auth()->user()->esStaff())
                        <a href="{{ route('licencias.index') }}" wire:navigate
                           class="z-nav-link {{ request()->routeIs('licencias*') ? 'is-active' : '' }}"
                           @if (request()->routeIs('licencias*')) aria-current="page" @endif>
                            <x-z.icon name="file-text" class="size-[18px] shrink-0" />
                            <span>Licencias</span>
                            @if (request()->routeIs('licencias*')) <span class="z-nav-dot"></span> @endif
                        </a>
                        <a href="{{ route('promociones.index') }}" wire:navigate
                           class="z-nav-link {{ request()->routeIs('promociones.*') ? 'is-active' : '' }}"
                           @if (request()->routeIs('promociones.*')) aria-current="page" @endif>
                            <x-z.icon name="spark" class="size-[18px] shrink-0" />
                            <span>Promociones</span>
                            @if (request()->routeIs('promociones.*')) <span class="z-nav-dot"></span> @endif
                        </a>

                        <a href="{{ route('usuarios.index') }}" wire:navigate
                           class="z-nav-link {{ request()->routeIs('usuarios.*') ? 'is-active' : '' }}"
                           @if (request()->routeIs('usuarios.*')) aria-current="page" @endif>
                            <x-z.icon name="users" class="size-[18px] shrink-0" />
                            <span>Usuarios</span>
                            @if (request()->routeIs('usuarios.*')) <span class="z-nav-dot"></span> @endif
                        </a>

                        <a href="{{ route('reportes.index') }}" wire:navigate
                           class="z-nav-link {{ request()->routeIs('reportes.*') ? 'is-active' : '' }}"
                           @if (request()->routeIs('reportes.*')) aria-current="page" @endif>
                            <x-z.icon name="bar-chart" class="size-[18px] shrink-0" />
                            <span>Reportes</span>
                            @if (request()->routeIs('reportes.*')) <span class="z-nav-dot"></span> @endif
                        </a>
                    @endif
                </nav>

                <nav class="z-sidebar-section" aria-label="Cuenta">
                    <p class="z-sidebar-label">Cuenta</p>

                    <a href="{{ route('historial.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('historial.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('historial.*')) aria-current="page" @endif>
                        <x-z.icon name="clock" class="size-[18px] shrink-0" />
                        <span>Historial</span>
                        @if (request()->routeIs('historial.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('prestamos.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('prestamos.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('prestamos.*')) aria-current="page" @endif>
                        <x-z.icon name="bookmark" class="size-[18px] shrink-0" />
                        <span>Préstamos</span>
                        @if (request()->routeIs('prestamos.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('favoritos.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('favoritos.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('favoritos.*')) aria-current="page" @endif>
                        <x-z.icon name="heart" class="size-[18px] shrink-0" />
                        <span>Favoritos</span>
                        @if (request()->routeIs('favoritos.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('pagos.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('pagos.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('pagos.*')) aria-current="page" @endif>
                        <x-z.icon name="credit-card" class="size-[18px] shrink-0" />
                        <span>Pagos</span>
                        @if (request()->routeIs('pagos.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('notificaciones.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('notificaciones.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('notificaciones.*')) aria-current="page" @endif>
                        <x-z.icon name="bell" class="size-[18px] shrink-0" />
                        <span>Notificaciones</span>
                        @if (request()->routeIs('notificaciones.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('suscripciones.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('suscripciones.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('suscripciones.*')) aria-current="page" @endif>
                        <x-z.icon name="check" class="size-[18px] shrink-0" />
                        <span>Suscripciones</span>
                        @if (request()->routeIs('suscripciones.*')) <span class="z-nav-dot"></span> @endif
                    </a>

                    <a href="{{ route('planes.index') }}" wire:navigate
                       class="z-nav-link {{ request()->routeIs('planes.*') ? 'is-active' : '' }}"
                       @if (request()->routeIs('planes.*')) aria-current="page" @endif>
                        <x-z.icon name="layers" class="size-[18px] shrink-0" />
                        <span>Planes</span>
                        @if (request()->routeIs('planes.*')) <span class="z-nav-dot"></span> @endif
                    </a>


                </nav>

                <div class="z-sidebar-footer">
                    <x-z.user-menu :compact="false" />
                </div>
            </aside>

            <div class="min-w-0">
                <header class="z-topbar">
                    <button type="button" class="z-icon-button z-mobile-only" @click="open = true" aria-label="Abrir menú">
                        <x-z.icon name="menu" class="size-4" />
                    </button>

                    <div class="z-breadcrumb">{{ $section }}</div>
                </header>

                <main class="z-main">
                    {{ $slot }}
                </main>
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

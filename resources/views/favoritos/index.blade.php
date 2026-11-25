<x-layouts::app :title="__('Favoritos')">
    @php
        $usuario = auth()->user();
        $esStaff = $usuario->esStaff();
        $suscripcionActiva = $esStaff ? null : $usuario->suscripcionActiva()->with('plan')->first();
        $motivoBloqueo = $esStaff || ! $suscripcionActiva || ! $usuario->tieneAccesoLimitadoPorPrestamos()
            ? null
            : $usuario->motivoAccesoLimitado();
        $puedeLeer = $esStaff || ($suscripcionActiva !== null && $motivoBloqueo === null);
    @endphp

    <div class="z-page">
        <x-z.page-header title="Mis favoritos" subtitle="Los libros que has guardado para leer después.">
            <x-slot name="actions">
                <a href="{{ route('libros.index') }}" wire:navigate class="btn btn-primary">
                    <x-z.icon name="book-open" class="size-4" />
                    Explorar catálogo
                </a>
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por título, descripción o autor..." :resultados="$favoritos->total()" />

        <div class="z-records">
            @forelse ($favoritos as $favorito)
                @php
                    $libro = $favorito->libro;
                    $incluidoEnPlan = ! $libro || $esStaff || ! $suscripcionActiva?->plan || $suscripcionActiva->plan->incluyeLibro($libro);
                    $usaContador = $libro?->usaContadorLecturas() ?? false;
                    $prestamoActivo = $usaContador ? $libro->prestamoActivo() : null;
                    $prestamoDelUsuario = $usaContador ? $libro->prestamoActivoDe(auth()->id()) : null;
                    $lecturasAgotadas = $usaContador && $libro->lecturasAgotadas();
                    $licenciaContador = $usaContador
                        ? ($prestamoDelUsuario?->licencia ?? $prestamoActivo?->licencia ?? $libro->licenciaParaPrestamo())
                        : null;
                    $puedeLeerLibro = $usaContador
                        ? $prestamoDelUsuario !== null
                            && ! $prestamoDelUsuario->estaVencido()
                            && ! $usuario->tieneAccesoLimitadoPorPrestamos()
                        : $libro && $puedeLeer && $incluidoEnPlan;
                @endphp
                @if ($libro)
                    <article class="z-record">
                        <div class="z-record-top">
                            <x-z.badge variant="accent">
                                <x-z.icon name="heart" class="size-3.5" />
                                Favorito
                            </x-z.badge>
                            @if ($usaContador)
                                <x-z.badge variant="accent">Cantidad limitada</x-z.badge>
                                @if ($lecturasAgotadas)
                                    <x-z.badge variant="danger">Sin lecturas</x-z.badge>
                                @elseif ($prestamoActivo)
                                    <x-z.badge variant="warning">Prestado</x-z.badge>
                                @else
                                    <x-z.badge variant="info">{{ $libro->lecturasDisponibles() }} lecturas</x-z.badge>
                                @endif
                            @elseif ($motivoBloqueo)
                                <x-z.badge variant="danger">Acceso bloqueado</x-z.badge>
                            @elseif (! $incluidoEnPlan)
                                <x-z.badge variant="accent">Fuera de tu plan</x-z.badge>
                            @endif
                        </div>

                        <h2 class="z-record-title">{{ $libro->titulo }}</h2>
                        <p class="z-record-sub line-clamp-2">{{ \Illuminate\Support\Str::limit($libro->descripcion, 130) }}</p>

                        <div class="z-record-authors">
                            @forelse ($libro->autores as $autor)
                                <span class="z-hint">{{ $autor->nombre }}</span>
                            @empty
                                <span class="z-hint">Sin autor</span>
                            @endforelse
                        </div>

                        @if ($licenciaContador)
                            <p class="z-hint mt-2">Préstamo {{ number_format($licenciaContador->precioPrestamo(), 2, ',', '.') }} · {{ $licenciaContador->diasPrestamo() }} días para devolver</p>
                        @endif

                        <div class="z-record-actions">
                            @if ($usaContador)
                                @if ($libro->archivo_pdf && $puedeLeerLibro)
                                    <a href="{{ route('libros.leer', $libro) }}?v={{ md5($libro->archivo_pdf) }}"
                                       class="btn btn-secondary btn-sm"
                                       aria-label="Leer {{ $libro->titulo }}">
                                        <x-z.icon name="eye" class="size-4" />
                                        Leer
                                    </a>
                                    <span class="z-hint">Devuelve antes del {{ $prestamoDelUsuario->f_finalizacion?->format('d/m/Y') }}</span>
                                    <form method="POST" action="{{ route('prestamos.devolver', $prestamoDelUsuario) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm" aria-label="Devolver {{ $libro->titulo }}">
                                            <x-z.icon name="rotate-ccw" class="size-4" />
                                            Devolver
                                        </button>
                                    </form>
                                @elseif ($libro->archivo_pdf && $prestamoDelUsuario && $prestamoDelUsuario->estaVencido())
                                    <a href="{{ route('prestamos.index') }}" class="btn btn-secondary btn-sm">
                                        <x-z.icon name="bookmark" class="size-4" />
                                        Regularizar acceso
                                    </a>
                                @elseif ($lecturasAgotadas)
                                    <span class="z-hint">No disponible</span>
                                @elseif ($prestamoActivo)
                                    <span class="z-hint">Prestado a otro cliente</span>
                                @elseif ($libro->archivo_pdf)
                                    <form method="POST" action="{{ route('prestamos.store', $libro) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" aria-label="Pedir préstamo de {{ $libro->titulo }}">
                                            <x-z.icon name="bookmark" class="size-4" />
                                            Prestar
                                        </button>
                                    </form>
                                @endif
                            @elseif ($libro->archivo_pdf && $puedeLeerLibro)
                                <a href="{{ route('libros.leer', $libro) }}?v={{ md5($libro->archivo_pdf) }}"
                                   class="btn btn-secondary btn-sm"
                                   aria-label="Leer {{ $libro->titulo }}">
                                    <x-z.icon name="eye" class="size-4" />
                                    Leer
                                </a>
                            @elseif ($libro->archivo_pdf && $motivoBloqueo)
                                <a href="{{ route('prestamos.index') }}" wire:navigate class="btn btn-secondary btn-sm">
                                    <x-z.icon name="bookmark" class="size-4" />
                                    Regularizar acceso
                                </a>
                            @elseif ($libro->archivo_pdf && ! $incluidoEnPlan)
                                <a href="{{ route('planes.index') }}" wire:navigate class="btn btn-secondary btn-sm">
                                    <x-z.icon name="layers" class="size-4" />
                                    Cambiar plan
                                </a>
                            @elseif ($libro->archivo_pdf)
                                <a href="{{ route('planes.index') }}" wire:navigate class="btn btn-secondary btn-sm">
                                    <x-z.icon name="arrow-right" class="size-4" />
                                    Suscribirme
                                </a>
                            @endif

                            <form method="POST" action="{{ route('favoritos.destroy', $libro) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm text-danger">
                                    <x-z.icon name="x" class="size-4" />
                                    Quitar
                                </button>
                            </form>
                        </div>
                    </article>
                @endif
            @empty
                <div class="z-record-empty">
                    <x-z.vacio titulo="Todavía no tienes favoritos." copy="Pulsa el corazón en un libro del catálogo para guardarlo aquí." />
                </div>
            @endforelse
        </div>

        {{ $favoritos->links() }}
    </div>
</x-layouts::app>

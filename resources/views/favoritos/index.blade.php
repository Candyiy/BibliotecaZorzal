<x-layouts::app :title="__('Favoritos')">
    @php
        $puedeLeer = auth()->user()->esStaff() || auth()->user()->suscripcionActiva()->exists();
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
                @php $libro = $favorito->libro; @endphp
                @if ($libro)
                    <article class="z-record">
                        <div class="z-record-top">
                            <x-z.badge variant="accent">
                                <x-z.icon name="heart" class="size-3.5" />
                                Favorito
                            </x-z.badge>
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

                        <div class="z-record-actions">
                            @if ($libro->archivo_pdf && $puedeLeer)
                                <a href="{{ route('libros.leer', $libro) }}" wire:navigate class="btn btn-secondary btn-sm">
                                    <x-z.icon name="eye" class="size-4" />
                                    Leer
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

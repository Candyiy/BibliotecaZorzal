<x-layouts::app :title="__('Autores')">
    @php $esStaff = auth()->user()->esStaff(); @endphp

    <div class="z-page">
        <x-z.page-header title="Autores" subtitle="{{ $esStaff ? 'Gestiona las personas asociadas a los títulos de la biblioteca.' : 'Conoce a las personas detrás de los títulos de la colección.' }}">
            <x-slot name="actions">
                @if ($esStaff)
                    <button type="button" command="show-modal" commandfor="crear-autor" class="btn btn-primary">
                        <x-z.icon name="plus" class="size-4" />
                        Nuevo autor
                    </button>
                @endif
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por nombre o nacionalidad..." :resultados="$autores->count()" />

        <div class="z-records">
            @forelse ($autores as $autor)
                @php
                    $iniciales = collect(preg_split('/\s+/', trim($autor->nombre)))
                        ->filter()
                        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
                        ->take(2)
                        ->join('');
                    $obras = $autor->libros->sortBy('titulo');
                @endphp
                <article class="z-record">
                    <div class="z-record-top">
                        <span class="z-record-avatar" aria-hidden="true">{{ $iniciales }}</span>
                        <x-z.badge variant="accent">{{ $autor->libros_count }} {{ $autor->libros_count === 1 ? 'libro' : 'libros' }}</x-z.badge>
                    </div>

                    <h2 class="z-record-title">{{ $autor->nombre }}</h2>
                    <p class="z-record-sub">{{ $autor->nacionalidad ?: 'Nacionalidad sin registrar' }}</p>

                    <div class="z-record-meta">
                        <span>
                            <x-z.icon name="calendar" class="size-4" />
                            {{ $autor->f_nacimiento?->format('d/m/Y') ?? 'Fecha desconocida' }}
                        </span>
                    </div>

                    <div class="z-record-works">
                        @if ($obras->isNotEmpty())
                            @foreach ($obras->take(3) as $obra)
                                <x-z.badge variant="neutral">{{ $obra->titulo }}</x-z.badge>
                            @endforeach
                            @if ($obras->count() > 3)
                                <x-z.badge variant="neutral">+{{ $obras->count() - 3 }} más</x-z.badge>
                            @endif
                        @else
                            <span class="z-hint">Sin libros asignados</span>
                        @endif
                    </div>

                    @if ($esStaff)
                        <div class="z-record-actions">
                            <div class="z-row-actions">
                                <button type="button" command="show-modal" commandfor="editar-{{ $autor->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $autor->nombre }}">
                                    <x-z.icon name="pencil" class="size-4" />
                                </button>
                                <button type="button" command="show-modal" commandfor="eliminar-autor-{{ $autor->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $autor->nombre }}">
                                    <x-z.icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="z-record-empty">
                    <x-z.vacio titulo="Todavía no hay autores." :copy="$esStaff ? 'Usa «Nuevo autor» para agregar el primero.' : 'Vuelve pronto para conocer a los autores de la colección.'" />
                </div>
            @endforelse
        </div>
    </div>

    @if ($esStaff)
    <!-- Modal: crear autor -->
    <dialog id="crear-autor" @if ($errors->any() && old('_modal') === 'crear') open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Nuevo autor</h2>
                <p class="dialog-copy">Registra los datos básicos del autor.</p>
            </div>
            <button type="button" command="close" commandfor="crear-autor" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('autores.partials.errores', ['modal' => 'crear'])

        <form action="{{ route('autores.store') }}" method="POST" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <div>
                <label for="crear-autor-nombre" class="field-label">Nombre</label>
                <input id="crear-autor-nombre" name="nombre" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('nombre') }}" class="field" placeholder="Ej. Miguel de Cervantes">
            </div>

            <div>
                <label for="crear-autor-nacionalidad" class="field-label">Nacionalidad</label>
                <input id="crear-autor-nacionalidad" name="nacionalidad" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('nacionalidad') }}" class="field" placeholder="Ej. Española">
            </div>

            <div>
                <label for="crear-autor-nacimiento" class="field-label">Fecha de nacimiento</label>
                <input id="crear-autor-nacimiento" name="f_nacimiento" type="date" required max="{{ now()->format('Y-m-d') }}" value="{{ old('f_nacimiento') }}" class="field">
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-autor" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-autor" class="btn btn-primary">Guardar autor</button>
            </div>
        </form>
    </dialog>

    @foreach ($autores as $autor)
        <!-- Modal: editar autor -->
        @php $modal = 'editar-'.$autor->id; @endphp
        <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Editar autor</h2>
                    <p class="dialog-copy">{{ $autor->nombre }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('autores.partials.errores', ['modal' => $modal])

            <form action="{{ route('autores.update', $autor) }}" method="POST" class="field-grid">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="{{ $modal }}">

                <div>
                    <label for="editar-autor-nombre-{{ $autor->id }}" class="field-label">Nombre</label>
                    <input id="editar-autor-nombre-{{ $autor->id }}" name="nombre" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('nombre', $autor->nombre) }}" class="field">
                </div>

                <div>
                    <label for="editar-autor-nacionalidad-{{ $autor->id }}" class="field-label">Nacionalidad</label>
                    <input id="editar-autor-nacionalidad-{{ $autor->id }}" name="nacionalidad" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('nacionalidad', $autor->nacionalidad) }}" class="field">
                </div>

                <div>
                    <label for="editar-autor-nacimiento-{{ $autor->id }}" class="field-label">Fecha de nacimiento</label>
                    <input id="editar-autor-nacimiento-{{ $autor->id }}" name="f_nacimiento" type="date" required max="{{ now()->format('Y-m-d') }}" value="{{ old('f_nacimiento', $autor->f_nacimiento?->format('Y-m-d')) }}" class="field">
                </div>

                <div class="form-actions">
                    <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: confirmar eliminación -->
        <dialog id="eliminar-autor-{{ $autor->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Eliminar autor</h2>
                    <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                </div>
                <button type="button" command="close" commandfor="eliminar-autor-{{ $autor->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres eliminar «{{ $autor->nombre }}»? También se quitará de sus libros.</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="eliminar-autor-{{ $autor->id }}" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('autores.destroy', $autor) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" command="close" commandfor="eliminar-autor-{{ $autor->id }}" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </dialog>
    @endforeach

    @endif

    @include('partials.modal-scripts')
</x-layouts::app>

<x-layouts::app :title="__('Libros')">
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
        <x-z.page-header title="Libros" subtitle="{{ $esStaff ? 'Gestiona el catálogo, edita los datos y abre los archivos PDF.' : 'Explora la colección, lee los PDF y comparte tus reseñas.' }}">
            <x-slot name="actions">
                @if ($esStaff)
                    <button type="button" command="show-modal" commandfor="crear-categoria" class="btn btn-secondary">
                        <x-z.icon name="plus" class="size-4" />
                        Nueva categoría
                    </button>

                    <button type="button" command="show-modal" commandfor="crear-libro" class="btn btn-primary">
                        <x-z.icon name="plus" class="size-4" />
                        Nuevo libro
                    </button>
                @endif
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por título, descripción, autor o categoría..." :resultados="$libros->total()" :filtros="$opcionesFiltros" />

        <div class="z-records">
            @forelse ($libros as $libro)
                @php
                    $esFavorito = $libro->favoritos->contains('user_id', auth()->id());
                    $incluidoEnPlan = $esStaff || ! $suscripcionActiva?->plan || $suscripcionActiva->plan->incluyeLibro($libro);
                    $usaContador = $libro->usaContadorLecturas();
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
                        : $puedeLeer && $incluidoEnPlan;
                    $ctaEsPrestamo = ! $usaContador && $planesPorLibro->has($libro->id);
                @endphp
                <article class="z-record">
                    <div class="z-record-top">
                        <x-z.badge variant="{{ $libro->archivo_pdf ? 'success' : 'warning' }}">
                            {{ $libro->archivo_pdf ? 'PDF disponible' : 'Sin PDF' }}
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
                            <button type="button"
                                    command="show-modal"
                                    commandfor="ver-autor-{{ $autor->id }}"
                                    class="btn btn-ghost btn-sm"
                                    aria-label="Ver autor {{ $autor->nombre }}">
                                <x-z.icon name="users" class="size-3.5" />
                                <span class="truncate">{{ $autor->nombre }}</span>
                            </button>
                        @empty
                            <span class="z-hint">Sin autor</span>
                        @endforelse
                    </div>

                    @if ($libro->categorias->isNotEmpty())
                        <div class="z-record-categories">
                            @foreach ($libro->categorias as $categoria)
                                <x-z.badge variant="accent">{{ $categoria->nombre }}</x-z.badge>
                            @endforeach
                        </div>
                    @endif

                    <div class="z-record-meta">
                        <span>
                            <x-z.icon name="calendar" class="size-4" />
                            {{ $libro->fecha_publicacion?->format('d/m/Y') ?? 'Fecha desconocida' }}
                        </span>
                        @if ($licenciaContador)
                            <span class="text-brass-ink tabular-nums">
                                Préstamo {{ number_format($licenciaContador->precioPrestamo(), 2, ',', '.') }} · {{ $licenciaContador->diasPrestamo() }} días
                            </span>
                        @endif
                        @if ($libro->resenas_count > 0)
                            <span class="z-record-score">
                                <span class="z-stars" aria-hidden="true">
                                    @for ($estrella = 1; $estrella <= 5; $estrella++)
                                        <x-z.icon name="star" class="size-3.5 {{ $estrella <= (int) round((float) $libro->resenas_avg_puntaje) ? 'is-active' : '' }}" />
                                    @endfor
                                </span>
                                <span class="tabular-nums">{{ number_format((float) $libro->resenas_avg_puntaje, 1, ',', '.') }} / 5 · {{ $libro->porcentajeResenas() }}%</span>
                                <span>{{ $libro->resenas_count }} {{ $libro->resenas_count === 1 ? 'reseña' : 'reseñas' }}</span>
                            </span>
                        @else
                            <span>Sin reseñas</span>
                        @endif
                    </div>

                    <div class="z-record-actions">
                        @if ($usaContador)
                            @if ($libro->archivo_pdf && $puedeLeerLibro)
                                <a href="{{ route('libros.leer', $libro) }}?v={{ md5($libro->archivo_pdf ?? '') }}"
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
                                <a href="{{ route('prestamos.index') }}"
                                   class="btn btn-secondary btn-sm"
                                   aria-label="Regularizar el acceso a {{ $libro->titulo }}">
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
                                <span class="z-hint">Pagas {{ number_format($licenciaContador?->precioPrestamo() ?? 0, 2, ',', '.') }} y lo devuelves en {{ $licenciaContador?->diasPrestamo() ?? 24 }} días.</span>
                            @endif
                        @elseif ($libro->archivo_pdf && $puedeLeerLibro)
                            <a href="{{ route('libros.leer', $libro) }}?v={{ md5($libro->archivo_pdf ?? '') }}"
                               class="btn btn-secondary btn-sm"
                               aria-label="Leer {{ $libro->titulo }}">
                                <x-z.icon name="eye" class="size-4" />
                                Leer
                            </a>
                        @elseif ($libro->archivo_pdf && $motivoBloqueo)
                            <a href="{{ route('prestamos.index') }}"
                               class="btn btn-secondary btn-sm"
                               aria-label="Regularizar el acceso a {{ $libro->titulo }}">
                                <x-z.icon name="bookmark" class="size-4" />
                                Regularizar acceso
                            </a>
                        @elseif ($libro->archivo_pdf && ! $incluidoEnPlan)
                            <a href="{{ route('planes.index') }}"
                               class="btn btn-secondary btn-sm"
                               aria-label="Cambiar de plan para leer {{ $libro->titulo }}">
                                <x-z.icon name="layers" class="size-4" />
                                Cambiar plan
                            </a>
                        @elseif ($libro->archivo_pdf)
                            <a href="{{ route('planes.index') }}"
                               class="btn btn-secondary btn-sm"
                               aria-label="{{ $ctaEsPrestamo ? 'Pedir préstamo de' : 'Suscríbete para leer' }} {{ $libro->titulo }}">
                                <x-z.icon name="arrow-right" class="size-4" />
                                {{ $ctaEsPrestamo ? 'Préstamo' : 'Suscribirme' }}
                            </a>
                        @endif

                        <button type="button" command="show-modal" commandfor="resenas-{{ $libro->id }}" class="btn btn-secondary btn-sm" aria-label="Reseñas de {{ $libro->titulo }}">
                            <x-z.icon name="star" class="size-4" />
                            Reseñar
                        </button>

                        @if (! $usaContador && $puedeLeerLibro && $libro->licencias->isNotEmpty())
                            <form method="POST" action="{{ route('prestamos.store', $libro) }}">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="Solicitar préstamo de {{ $libro->titulo }}">
                                    <x-z.icon name="bookmark" class="size-4" />
                                    Pedir préstamo
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ $esFavorito ? route('favoritos.destroy', $libro) : route('favoritos.store', $libro) }}">
                            @csrf
                            @if ($esFavorito)
                                @method('DELETE')
                            @endif
                            <button type="submit" class="btn btn-ghost btn-sm {{ $esFavorito ? 'text-brass-ink' : '' }}" aria-label="{{ $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                                <x-z.icon name="heart" class="size-4" />
                                {{ $esFavorito ? 'En favoritos' : 'Favorito' }}
                            </button>
                        </form>

                        @if ($esStaff)
                            <div class="z-row-actions">
                                <button type="button" command="show-modal" commandfor="editar-{{ $libro->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $libro->titulo }}">
                                    <x-z.icon name="pencil" class="size-4" />
                                </button>

                                <button type="button" command="show-modal" commandfor="eliminar-libro-{{ $libro->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $libro->titulo }}">
                                    <x-z.icon name="trash" class="size-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="z-record-empty">
                    <x-z.vacio titulo="Todavía no hay libros." :copy="$esStaff ? 'Usa «Nuevo libro» para agregar el primero a la colección.' : 'Vuelve pronto: estamos preparando la colección.'" />
                </div>
            @endforelse
        </div>

        {{ $libros->links() }}
    </div>

    @if ($esStaff)
    <!-- Modal: crear libro -->
    <dialog id="crear-libro" @if ($errors->any() && old('_modal') === 'crear') open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Nuevo libro</h2>
            </div>
            <button type="button" command="close" commandfor="crear-libro" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('libros.partials.errores', ['modal' => 'crear'])

        <form action="{{ route('libros.store') }}" method="POST" enctype="multipart/form-data" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <div>
                <label for="crear-titulo" class="field-label">Título</label>
                <input id="crear-titulo" name="titulo" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('titulo') }}" class="field" placeholder="Ej. Cien años de soledad">
            </div>

            <div>
                <label for="crear-descripcion" class="field-label">Descripción</label>
                <textarea id="crear-descripcion" name="descripcion" rows="3" required title="No se permiten números" data-sin-numeros class="field" placeholder="Breve descripción del libro">{{ old('descripcion') }}</textarea>
            </div>

            <div class="field-grid two">
                <div>
                    <label for="crear-fecha" class="field-label">Fecha de publicación</label>
                    <input id="crear-fecha" name="fecha_publicacion" type="date" required value="{{ old('fecha_publicacion') }}" class="field">
                </div>

                <div>
                    <label for="crear-autor" class="field-label">Autor (opcional)</label>
                    <select id="crear-autor" name="autor_id" class="field">
                        <option value="">Sin autor</option>
                        @foreach ($autores as $autor)
                            <option value="{{ $autor->id }}" @selected(old('autor_id') == $autor->id)>{{ $autor->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="crear-categorias" class="field-label">Categorías (opcional)</label>
                <select id="crear-categorias" name="categorias[]" multiple size="4" class="field">
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(in_array($categoria->id, array_map('intval', (array) old('categorias', []))))>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
                
            </div>

            <div>
                <label for="crear-pdf" class="field-label">Archivo PDF</label>
                <input id="crear-pdf" name="archivo_pdf" type="file" accept="application/pdf,.pdf" required class="field">
                <p class="field-help">Máximo 20 MB.</p>
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-libro" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-libro" class="btn btn-primary">Guardar libro</button>
            </div>
        </form>
    </dialog>

    @if ($esStaff)
    <!-- Modal: crear categoría -->
    <dialog id="crear-categoria" @if ($errors->any() && old('_modal') === 'crear-categoria') open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Nueva categoría</h2>
                <p class="dialog-copy">Úsala para clasificar los libros del catálogo.</p>
            </div>
            <button type="button" command="close" commandfor="crear-categoria" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('libros.partials.errores', ['modal' => 'crear-categoria'])

        <form action="{{ route('categorias.store') }}" method="POST" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear-categoria">

            <div>
                <label for="crear-categoria-nombre" class="field-label">Nombre</label>
                <input id="crear-categoria-nombre" name="nombre" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('nombre') }}" class="field" placeholder="Ej. Novela histórica">
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-categoria" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-categoria" class="btn btn-primary">Guardar categoría</button>
            </div>
        </form>
    </dialog>
    @endif

    @foreach ($libros as $libro)
        <!-- Modal: editar libro -->
        @php
            $modal = 'editar-'.$libro->id;
            $categoriasSeleccionadas = array_map('intval', (array) old('categorias', $libro->categorias->pluck('id')->all()));
        @endphp
        <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Editar libro</h2>
                    <p class="dialog-copy">{{ $libro->titulo }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('libros.partials.errores', ['modal' => $modal])

            <form action="{{ route('libros.update', $libro) }}" method="POST" enctype="multipart/form-data" class="field-grid">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="{{ $modal }}">

                <div>
                    <label for="editar-titulo-{{ $libro->id }}" class="field-label">Título</label>
                    <input id="editar-titulo-{{ $libro->id }}" name="titulo" type="text" required maxlength="255" pattern="[^0-9]*" title="No se permiten números" data-sin-numeros value="{{ old('titulo', $libro->titulo) }}" class="field">
                </div>

                <div>
                    <label for="editar-descripcion-{{ $libro->id }}" class="field-label">Descripción</label>
                    <textarea id="editar-descripcion-{{ $libro->id }}" name="descripcion" rows="3" required title="No se permiten números" data-sin-numeros class="field">{{ old('descripcion', $libro->descripcion) }}</textarea>
                </div>

                <div class="field-grid two">
                    <div>
                        <label for="editar-fecha-{{ $libro->id }}" class="field-label">Fecha de publicación</label>
                        <input id="editar-fecha-{{ $libro->id }}" name="fecha_publicacion" type="date" required value="{{ old('fecha_publicacion', $libro->fecha_publicacion?->format('Y-m-d')) }}" class="field">
                    </div>

                    <div>
                        <label for="editar-autor-{{ $libro->id }}" class="field-label">Autor (opcional)</label>
                        <select id="editar-autor-{{ $libro->id }}" name="autor_id" class="field">
                            <option value="">Sin autor</option>
                            @foreach ($autores as $autor)
                                <option value="{{ $autor->id }}" @selected(old('autor_id', $libro->autores->first()?->id) == $autor->id)>{{ $autor->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="editar-categorias-{{ $libro->id }}" class="field-label">Categorías (opcional)</label>
                    <select id="editar-categorias-{{ $libro->id }}" name="categorias[]" multiple size="4" class="field">
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(in_array($categoria->id, $categoriasSeleccionadas))>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="field-help">Puedes seleccionar varias con Ctrl/Cmd.</p>
                </div>

                <div>
                    <label for="editar-pdf-{{ $libro->id }}" class="field-label">Reemplazar PDF (opcional)</label>
                    <input id="editar-pdf-{{ $libro->id }}" name="archivo_pdf" type="file" accept="application/pdf,.pdf" class="field">
                </div>

                <div class="form-actions">
                    <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: confirmar eliminación -->
        <dialog id="eliminar-libro-{{ $libro->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Eliminar libro</h2>
                    <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                </div>
                <button type="button" command="close" commandfor="eliminar-libro-{{ $libro->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres eliminar «{{ $libro->titulo }}»? También se borrará su archivo PDF.</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="eliminar-libro-{{ $libro->id }}" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('libros.destroy', $libro) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" command="close" commandfor="eliminar-libro-{{ $libro->id }}" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </dialog>

        @endforeach
    @endif

    @php
        $autoresDelCatalogo = $libros->getCollection()
            ->pluck('autores')
            ->flatten()
            ->unique('id')
            ->sortBy('nombre');
    @endphp

    @foreach ($autoresDelCatalogo as $autor)
        <!-- Modal: ver autor -->
        <dialog id="ver-autor-{{ $autor->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">{{ $autor->nombre }}</h2>
                    <p class="dialog-copy">Datos del autor</p>
                </div>
                <button type="button" command="close" commandfor="ver-autor-{{ $autor->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <dl class="field-grid two">
                <div>
                    <dt class="field-label">Nacionalidad</dt>
                    <dd class="text-sm font-medium text-ink">{{ $autor->nacionalidad ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="field-label">Fecha de nacimiento</dt>
                    <dd class="text-sm font-medium text-ink">{{ $autor->f_nacimiento?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>

            <div class="mt-5">
                <p class="field-label">Obras en la biblioteca</p>
                <div class="mt-1 flex flex-wrap gap-2">
                    @forelse ($autor->libros->sortBy('titulo') as $obra)
                        <x-z.badge variant="accent">{{ $obra->titulo }}</x-z.badge>
                    @empty
                        <p class="z-hint">Todavía no hay obras registradas.</p>
                    @endforelse
                </div>
            </div>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="ver-autor-{{ $autor->id }}" class="btn btn-secondary">Cerrar</button>
            </div>
        </dialog>
    @endforeach

    @foreach ($libros as $libro)
        <!-- Modal: reseñas del libro -->
        @php
            $modalResenas = 'resenas-'.$libro->id;
            $miResena = $libro->resenaDeUsuario(auth()->id());
            $otrasResenas = $libro->resenas->where('user_id', '!=', auth()->id())->sortByDesc('created_at');
        @endphp
        <dialog id="{{ $modalResenas }}" @if ($errors->any() && old('_modal') === $modalResenas) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Reseñas</h2>
                    <p class="dialog-copy">{{ $libro->titulo }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modalResenas }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('libros.partials.errores', ['modal' => $modalResenas])

            <div class="z-card">
                <p class="z-card-title">Calificación del libro</p>
                <p class="z-card-note">
                    @if ($libro->resenas_count > 0)
                        <span class="tabular-nums">{{ number_format((float) $libro->resenas_avg_puntaje, 1, ',', '.') }} / 5 · {{ $libro->porcentajeResenas() }}%</span>
                        <span>{{ $libro->resenas_count }} {{ $libro->resenas_count === 1 ? 'reseña' : 'reseñas' }}</span>
                    @else
                        Todavía no hay reseñas. Sé el primero en opinar.
                    @endif
                </p>
            </div>

            <div class="resena-list mt-4">
                @if ($miResena)
                    <article class="resena-item is-mia">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="cell-primary">Tu reseña · {{ $miResena->puntaje }}/5 ({{ $miResena->puntaje * 20 }}%)</span>
                            <span class="z-hint">{{ $miResena->created_at?->format('d/m/Y') }}</span>
                        </div>
                        <span class="z-stars" aria-label="{{ $miResena->puntaje }} de 5">
                            @for ($estrella = 1; $estrella <= 5; $estrella++)
                                <x-z.icon name="star" class="size-4 {{ $estrella <= $miResena->puntaje ? 'is-active' : '' }}" />
                            @endfor
                        </span>
                        <p class="mt-1 text-sm text-ink-soft">{{ $miResena->comentario }}</p>
                    </article>
                @else
                    <p class="text-sm text-ink-soft">Todavía no has reseñado este libro.</p>
                @endif

                @foreach ($otrasResenas as $resena)
                    <article class="resena-item">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="cell-primary">{{ $resena->user?->name ?? 'Lectura anónima' }}</span>
                            <span class="z-hint">{{ $resena->created_at?->format('d/m/Y') }}</span>
                        </div>
                        <span class="z-stars" aria-label="{{ $resena->puntaje }} de 5">
                            @for ($estrella = 1; $estrella <= 5; $estrella++)
                                <x-z.icon name="star" class="size-4 {{ $estrella <= $resena->puntaje ? 'is-active' : '' }}" />
                            @endfor
                        </span>
                        <p class="mt-1 text-sm text-ink-soft">{{ $resena->comentario }}</p>
                    </article>
                @endforeach
            </div>

            <form id="form-resena-{{ $libro->id }}" action="{{ route('resenas.store', $libro) }}" method="POST" class="field-grid mt-5">
                @csrf
                <input type="hidden" name="_modal" value="{{ $modalResenas }}">

                <div>
                    <span class="field-label" id="puntaje-label-{{ $libro->id }}">Puntaje</span>
                    <div class="z-rating-input" role="radiogroup" aria-labelledby="puntaje-label-{{ $libro->id }}">
                        @for ($estrella = 5; $estrella >= 1; $estrella--)
                            <input type="radio" name="puntaje" id="puntaje-{{ $libro->id }}-{{ $estrella }}" value="{{ $estrella }}" @checked((int) old('puntaje', $miResena?->puntaje ?? 0) === $estrella)>
                            <label for="puntaje-{{ $libro->id }}-{{ $estrella }}" class="z-rating-star" title="{{ $estrella }} {{ $estrella === 1 ? 'estrella' : 'estrellas' }}">
                                <x-z.icon name="star" class="size-7" />
                                <span class="sr-only">{{ $estrella }} {{ $estrella === 1 ? 'estrella' : 'estrellas' }}</span>
                            </label>
                        @endfor
                    </div>
                    <p class="field-help">Elige entre 1 y 5 estrellas.</p>
                </div>

                <div>
                    <label for="comentario-{{ $libro->id }}" class="field-label">Comentario</label>
                    <textarea id="comentario-{{ $libro->id }}" name="comentario" rows="3" maxlength="255" required title="No se permiten números" data-sin-numeros class="field" placeholder="¿Qué te pareció este libro?">{{ old('comentario', $miResena?->comentario ?? '') }}</textarea>
                </div>
            </form>

            <div class="form-actions">
                <button type="button" command="close" commandfor="{{ $modalResenas }}" class="btn btn-secondary">Cancelar</button>
                <button type="submit" form="form-resena-{{ $libro->id }}" command="close" commandfor="{{ $modalResenas }}" class="btn btn-primary">{{ $miResena ? 'Actualizar reseña' : 'Publicar reseña' }}</button>
            </div>
        </dialog>
    @endforeach

    @include('partials.modal-scripts')
</x-layouts::app>

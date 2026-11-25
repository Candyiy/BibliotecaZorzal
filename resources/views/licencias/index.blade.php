<x-layouts::app :title="__('Licencias')">
    <div class="z-page">
        <x-z.page-header title="Licencias" subtitle="Administra los contratos editoriales y su asignación a los libros.">
            <x-slot name="actions">
                <button type="button" command="show-modal" commandfor="crear-editorial" class="btn btn-secondary">
                    <x-z.icon name="file-text" class="size-4" />
                    Nueva licencia editorial
                </button>
                <button type="button" command="show-modal" commandfor="crear-licencia" class="btn btn-primary">
                    <x-z.icon name="plus" class="size-4" />
                    Asignar licencia
                </button>
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por contrato, libro o licencia..." :resultados="$editoriales->count() + $licencias->count()" />

        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="z-page-title !text-2xl">Licencias editoriales</h2>
                    <p class="z-page-subtitle">Catálogo de contratos y su vigencia.</p>
                </div>
                <x-z.badge variant="accent">{{ $editoriales->count() }} contratos</x-z.badge>
            </div>

            <div class="z-table-wrap">
                <table class="z-table">
                    <thead>
                        <tr>
                            <th scope="col">Contrato</th>
                            <th scope="col">Vigencia</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Libros</th>
                            <th scope="col" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($editoriales as $editorial)
                            <tr>
                                <td>
                                    <span class="cell-primary">{{ $editorial->nombre }}</span>
                                    @if ($editorial->archivo_pdf)
                                        <span class="cell-sub">Contrato adjunto</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $editorial->f_inicio?->format('d/m/Y') ?? '—' }}
                                    <span class="text-muted"> — </span>
                                    {{ $editorial->f_finalizacion?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>@include('licencias.partials.estado', ['editorial' => $editorial])</td>
                                <td><x-z.badge variant="info">{{ $editorial->licencias_count }}</x-z.badge></td>
                                <td>
                                    <div class="z-row-actions">
                                        @if ($editorial->archivo_pdf)
                                            <button type="button"
                                                    command="show-modal"
                                                    commandfor="pdf-editorial-{{ $editorial->id }}"
                                                    data-pdf-url="{{ route('licencias-editoriales.pdf', $editorial) }}"
                                                    class="btn btn-secondary btn-sm"
                                                    aria-label="Ver PDF de {{ $editorial->nombre }}">
                                                <x-z.icon name="eye" class="size-4" />
                                                Ver PDF
                                            </button>
                                        @endif

                                        <button type="button" command="show-modal" commandfor="editar-editorial-{{ $editorial->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $editorial->nombre }}">
                                            <x-z.icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" command="show-modal" commandfor="eliminar-editorial-{{ $editorial->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $editorial->nombre }}">
                                            <x-z.icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-z.vacio titulo="Todavía no hay licencias editoriales." copy="Usa «Nueva licencia editorial» para crear la primera." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="z-page-title !text-2xl">Licencias asignadas</h2>
                    <p class="z-page-subtitle">Relación entre cada libro y su licencia editorial.</p>
                </div>
                <x-z.badge variant="info">{{ $licencias->count() }} asignaciones</x-z.badge>
            </div>

            <div class="z-table-wrap">
                <table class="z-table">
                    <thead>
                        <tr>
                            <th scope="col">Libro</th>
                            <th scope="col">Licencia editorial</th>
                            <th scope="col">Lecturas</th>
                            <th scope="col">Préstamo</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($licencias as $licencia)
                            @php $editorial = $licencia->editorial; @endphp
                            <tr>
                                <td><span class="cell-primary">{{ $licencia->libro->titulo }}</span></td>
                                <td>{{ $editorial->nombre }}</td>
                                <td>
                                    @if ($licencia->tieneContador())
                                        <x-z.badge variant="{{ $licencia->agotada() ? 'danger' : 'info' }}">
                                            {{ $licencia->lecturasDisponibles() }} disponibles
                                        </x-z.badge>
                                    @else
                                        <span class="z-hint">Sin límite</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($licencia->tieneContador())
                                        <span class="cell-primary tabular-nums">{{ number_format($licencia->precioPrestamo(), 2, ',', '.') }}</span>
                                        <span class="cell-sub">{{ $licencia->diasPrestamo() }} días para devolver</span>
                                    @else
                                        <span class="z-hint">Suscripción</span>
                                    @endif
                                </td>
                                <td>@include('licencias.partials.estado', ['editorial' => $editorial])</td>
                                <td>
                                    <div class="z-row-actions">
                                        <button type="button" command="show-modal" commandfor="editar-licencia-{{ $licencia->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar licencia de {{ $licencia->libro->titulo }}">
                                            <x-z.icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" command="show-modal" commandfor="eliminar-licencia-{{ $licencia->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar licencia de {{ $licencia->libro->titulo }}">
                                            <x-z.icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-z.vacio titulo="Todavía no hay licencias asignadas." copy="Usa «Asignar licencia» para crear la primera." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Modal: crear licencia editorial -->
    <dialog id="crear-editorial" @if ($errors->any() && old('_modal') === 'crear-editorial') open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Nueva licencia editorial</h2>
                <p class="dialog-copy">Registra un contrato y su periodo de vigencia.</p>
            </div>
            <button type="button" command="close" commandfor="crear-editorial" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('licencias.partials.errores', ['modal' => 'crear-editorial'])

        <form action="{{ route('licencias-editoriales.store') }}" method="POST" enctype="multipart/form-data" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear-editorial">

            <div>
                <label for="crear-editorial-nombre" class="field-label">Nombre</label>
                <input id="crear-editorial-nombre" name="nombre" type="text" required maxlength="255" value="{{ old('nombre') }}" class="field" placeholder="Ej. Contrato editorial 2026">
            </div>

            <div class="field-grid two">
                <div>
                    <label for="crear-editorial-inicio" class="field-label">Fecha de inicio</label>
                    <input id="crear-editorial-inicio" name="f_inicio" type="date" required min="{{ now()->format('Y-m-d') }}" title="No se permiten fechas anteriores a hoy" value="{{ old('f_inicio') }}" class="field">
                </div>
                <div>
                    <label for="crear-editorial-fin" class="field-label">Fecha de finalización</label>
                    <input id="crear-editorial-fin" name="f_finalizacion" type="date" required value="{{ old('f_finalizacion') }}" class="field">
                </div>
            </div>

            <div>
                <label for="crear-editorial-pdf" class="field-label">Archivo PDF (opcional)</label>
                <input id="crear-editorial-pdf" name="archivo_pdf" type="file" accept="application/pdf,.pdf" class="field">
                <p class="field-help">Máximo 20 MB.</p>
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-editorial" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-editorial" class="btn btn-primary">Guardar licencia</button>
            </div>
        </form>
    </dialog>

    @foreach ($editoriales as $editorial)
        <!-- Modal: editar licencia editorial -->
        @php $modal = 'editar-editorial-'.$editorial->id; @endphp
        <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Editar licencia editorial</h2>
                    <p class="dialog-copy">{{ $editorial->nombre }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('licencias.partials.errores', ['modal' => $modal])

            <form action="{{ route('licencias-editoriales.update', $editorial) }}" method="POST" enctype="multipart/form-data" class="field-grid">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="{{ $modal }}">

                <div>
                    <label for="editar-editorial-nombre-{{ $editorial->id }}" class="field-label">Nombre</label>
                    <input id="editar-editorial-nombre-{{ $editorial->id }}" name="nombre" type="text" required maxlength="255" value="{{ old('nombre', $editorial->nombre) }}" class="field">
                </div>

                <div class="field-grid two">
                    <div>
                        <label for="editar-editorial-inicio-{{ $editorial->id }}" class="field-label">Fecha de inicio</label>
                        <input id="editar-editorial-inicio-{{ $editorial->id }}" name="f_inicio" type="date" required min="{{ now()->format('Y-m-d') }}" title="No se permiten fechas anteriores a hoy" value="{{ old('f_inicio', $editorial->f_inicio?->format('Y-m-d')) }}" class="field">
                    </div>
                    <div>
                        <label for="editar-editorial-fin-{{ $editorial->id }}" class="field-label">Fecha de finalización</label>
                        <input id="editar-editorial-fin-{{ $editorial->id }}" name="f_finalizacion" type="date" required value="{{ old('f_finalizacion', $editorial->f_finalizacion?->format('Y-m-d')) }}" class="field">
                    </div>
                </div>

                <div>
                    <label for="editar-editorial-pdf-{{ $editorial->id }}" class="field-label">Reemplazar PDF (opcional)</label>
                    <input id="editar-editorial-pdf-{{ $editorial->id }}" name="archivo_pdf" type="file" accept="application/pdf,.pdf" class="field">
                </div>

                <div class="form-actions">
                    <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: confirmar eliminación de licencia editorial -->
        <dialog id="eliminar-editorial-{{ $editorial->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Eliminar licencia editorial</h2>
                    <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                </div>
                <button type="button" command="close" commandfor="eliminar-editorial-{{ $editorial->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres eliminar «{{ $editorial->nombre }}»? También se eliminarán sus asignaciones y su PDF.</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="eliminar-editorial-{{ $editorial->id }}" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('licencias-editoriales.destroy', $editorial) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" command="close" commandfor="eliminar-editorial-{{ $editorial->id }}" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </dialog>

        <!-- Modal: lector de PDF de la licencia -->
        <dialog id="pdf-editorial-{{ $editorial->id }}" class="dialog-box max-w-5xl p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div>
                    <h2 class="font-semibold text-ink">{{ $editorial->nombre }}</h2>
                    <p class="z-hint">Contrato de licencia</p>
                </div>
                <button type="button" command="close" commandfor="pdf-editorial-{{ $editorial->id }}" class="btn btn-secondary btn-sm">Cerrar</button>
            </div>
            <iframe title="Licencia {{ $editorial->nombre }}" class="h-[75vh] w-full bg-input" loading="lazy"></iframe>
        </dialog>
    @endforeach

    <!-- Modal: asignar licencia a un libro -->
    <dialog id="crear-licencia" @if ($errors->any() && old('_modal') === 'crear-licencia') open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Asignar licencia</h2>
                <p class="dialog-copy">Vincula un libro con una licencia editorial vigente.</p>
            </div>
            <button type="button" command="close" commandfor="crear-licencia" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('licencias.partials.errores', ['modal' => 'crear-licencia'])

        <form action="{{ route('licencias.store') }}" method="POST" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear-licencia">

            <div>
                <label for="crear-licencia-libro" class="field-label">Libro</label>
                <select id="crear-licencia-libro" name="libro_id" required class="field">
                    <option value="">Selecciona un libro</option>
                    @foreach ($libros as $libro)
                        <option value="{{ $libro->id }}" @selected(old('libro_id') == $libro->id)>{{ $libro->titulo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="crear-licencia-editorial" class="field-label">Licencia editorial</label>
                <select id="crear-licencia-editorial" name="licencia_editorial_id" required class="field">
                    <option value="">Selecciona una licencia</option>
                    @foreach ($editoriales as $editorial)
                        <option value="{{ $editorial->id }}" @selected(old('licencia_editorial_id') == $editorial->id)>{{ $editorial->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="crear-licencia-contador" class="field-label">Cantidad de lecturas</label>
                <input id="crear-licencia-contador"
                       type="number"
                       name="cantidad_lecturas"
                       min="0"
                       step="1"
                       value="{{ old('cantidad_lecturas') }}"
                       placeholder="Sin límite"
                       class="field">
                <p class="z-hint mt-1">Déjalo vacío para lectura libre con suscripción. Si pones un número, el libro se presta por separado y se bloquea al llegar a 0.</p>
            </div>

            <div>
                <label for="crear-licencia-dias" class="field-label">Días de préstamo</label>
                <input id="crear-licencia-dias"
                       type="number"
                       name="dias_prestamo"
                       min="1"
                       max="365"
                       step="1"
                       value="{{ old('dias_prestamo', 24) }}"
                       class="field">
                <p class="z-hint mt-1">Plazo que tiene el cliente para devolver el libro.</p>
            </div>

            <div>
                <label for="crear-licencia-precio" class="field-label">Precio del préstamo</label>
                <input id="crear-licencia-precio"
                       type="number"
                       name="precio_prestamo"
                       min="0"
                       max="99999999.99"
                       step="0.01"
                       value="{{ old('precio_prestamo', '0.00') }}"
                       class="field">
                <p class="z-hint mt-1">Se muestra en el catálogo y se registra como pago al pedir el préstamo.</p>
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-licencia" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-licencia" class="btn btn-primary">Asignar licencia</button>
            </div>
        </form>
    </dialog>

    @foreach ($licencias as $licencia)
        <!-- Modal: editar licencia asignada -->
        @php $modal = 'editar-licencia-'.$licencia->id; @endphp
        <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Editar licencia asignada</h2>
                    <p class="dialog-copy">{{ $licencia->libro->titulo }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('licencias.partials.errores', ['modal' => $modal])

            <form action="{{ route('licencias.update', $licencia) }}" method="POST" class="field-grid">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="{{ $modal }}">

                <div>
                    <label for="editar-licencia-libro-{{ $licencia->id }}" class="field-label">Libro</label>
                    <select id="editar-licencia-libro-{{ $licencia->id }}" name="libro_id" required class="field">
                        @foreach ($libros as $libro)
                            <option value="{{ $libro->id }}" @selected(old('libro_id', $licencia->libro_id) == $libro->id)>{{ $libro->titulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="editar-licencia-editorial-{{ $licencia->id }}" class="field-label">Licencia editorial</label>
                    <select id="editar-licencia-editorial-{{ $licencia->id }}" name="licencia_editorial_id" required class="field">
                        @foreach ($editoriales as $editorial)
                            <option value="{{ $editorial->id }}" @selected(old('licencia_editorial_id', $licencia->licencia_editorial_id) == $editorial->id)>{{ $editorial->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="editar-licencia-contador-{{ $licencia->id }}" class="field-label">Cantidad de lecturas</label>
                    <input id="editar-licencia-contador-{{ $licencia->id }}"
                           type="number"
                           name="cantidad_lecturas"
                           min="0"
                           step="1"
                           value="{{ old('cantidad_lecturas', $licencia->cantidad_lecturas) }}"
                           placeholder="Sin límite"
                           class="field">
                    <p class="z-hint mt-1">Déjalo vacío para lectura libre con suscripción. Si pones un número, el libro se presta por separado y se bloquea al llegar a 0.</p>
                </div>

                <div>
                    <label for="editar-licencia-dias-{{ $licencia->id }}" class="field-label">Días de préstamo</label>
                    <input id="editar-licencia-dias-{{ $licencia->id }}"
                           type="number"
                           name="dias_prestamo"
                           min="1"
                           max="365"
                           step="1"
                           value="{{ old('dias_prestamo', $licencia->dias_prestamo) }}"
                           class="field">
                    <p class="z-hint mt-1">Plazo que tiene el cliente para devolver el libro.</p>
                </div>

                <div>
                    <label for="editar-licencia-precio-{{ $licencia->id }}" class="field-label">Precio del préstamo</label>
                    <input id="editar-licencia-precio-{{ $licencia->id }}"
                           type="number"
                           name="precio_prestamo"
                           min="0"
                           max="99999999.99"
                           step="0.01"
                           value="{{ old('precio_prestamo', $licencia->precio_prestamo) }}"
                           class="field">
                    <p class="z-hint mt-1">Se muestra en el catálogo y se registra como pago al pedir el préstamo.</p>
                </div>

                <div class="form-actions">
                    <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: confirmar eliminación de licencia asignada -->
        <dialog id="eliminar-licencia-{{ $licencia->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Eliminar licencia asignada</h2>
                    <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                </div>
                <button type="button" command="close" commandfor="eliminar-licencia-{{ $licencia->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres quitar la licencia «{{ $licencia->editorial->nombre }}» del libro «{{ $licencia->libro->titulo }}»?</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="eliminar-licencia-{{ $licencia->id }}" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('licencias.destroy', $licencia) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" command="close" commandfor="eliminar-licencia-{{ $licencia->id }}" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </dialog>
    @endforeach

    @include('partials.modal-scripts')
</x-layouts::app>

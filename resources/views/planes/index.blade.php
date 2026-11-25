<x-layouts::app :title="__('Planes')">
    @php
        $esStaff = auth()->user()->esStaff();
        $bloqueaCambio = ! $esStaff && $suscripcionActiva !== null;
        $librosDisponibles = $libros;
    @endphp

    <div class="z-page">
        <x-z.page-header title="Planes" subtitle="{{ $esStaff ? 'Gestiona los planes de la biblioteca y elige tu suscripción.' : 'Elige la suscripción que mejor se ajuste a tu lectura.' }}">
            <x-slot name="actions">
                @if ($esStaff)
                    <button type="button" command="show-modal" commandfor="crear-plan" class="btn btn-primary">
                        <x-z.icon name="plus" class="size-4" />
                        Nuevo plan
                    </button>
                @endif
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por tipo de plan..." :resultados="$planes->count()" />

        @if ($suscripcionActiva)
            <div class="z-card flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="z-card-title">{{ $suscripcionActiva->plan?->esPorDias() ? 'Tu préstamo activo' : 'Tu suscripción activa' }}</p>
                    <p class="z-card-note">
                        Plan {{ $suscripcionActiva->plan ? ($suscripcionActiva->plan->esPorDias() ? 'por días' : $suscripcionActiva->plan->tipoEtiqueta()) : '—' }} ·
                        {{ $suscripcionActiva->plan?->modalidadEtiqueta() ?? '—' }} ·
                        {{ $suscripcionActiva->plan?->duracionEtiqueta() ?? '—' }} ·
                        desde {{ $suscripcionActiva->f_inicio?->format('d/m/Y') }} hasta {{ $suscripcionActiva->f_finalizacion?->format('d/m/Y') }}
                        @if ($suscripcionActiva->esPrueba())
                            · Período de prueba
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-z.badge variant="success">Activa</x-z.badge>
                    @if ($esStaff)
                        <button type="button" command="show-modal" commandfor="quitar-plan" class="btn btn-ghost btn-sm text-danger">
                            <x-z.icon name="x" class="size-4" />
                            Quitar plan
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Plan</th>
                        <th scope="col">Cobertura</th>
                        @if ($esStaff)
                            <th scope="col">Estado</th>
                        @endif
                        <th scope="col">Duración</th>
                        <th scope="col">Precio</th>
                        @if ($esStaff)
                            <th scope="col">Suscriptores</th>
                        @endif
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($planes as $plan)
                        <tr>
                            <td>
                                <span class="cell-primary">{{ $plan->tipoEtiqueta() }}</span>
                                @if ($suscripcionActiva?->plan_id === $plan->id)
                                    <span class="cell-sub">Tu plan actual</span>
                                @endif
                            </td>
                            <td>
                                <span class="cell-primary">{{ $plan->modalidadEtiqueta() }}</span>
                                <span class="cell-sub">{{ $plan->coberturaEtiqueta() }}</span>
                                @if ($plan->tienePrueba())
                                    <x-z.badge variant="accent">{{ $plan->trial_dias }} días de prueba</x-z.badge>
                                @endif
                                @foreach ($plan->beneficiosLista() as $beneficio)
                                    <span class="cell-sub">{{ $beneficio }}</span>
                                @endforeach
                            </td>
                            @if ($esStaff)
                                <td>
                                    <x-z.badge variant="{{ $plan->estaActivo() ? 'success' : 'neutral' }}">
                                        {{ $plan->estaActivo() ? 'Activa' : 'Inactiva' }}
                                    </x-z.badge>
                                </td>
                            @endif
                            <td>{{ $plan->duracionEtiqueta() }}</td>
                            <td>
                                @php $promocion = $plan->promocionActiva(); @endphp
                                @if ($promocion)
                                    <span class="cell-sub tabular-nums line-through">{{ number_format((float) $plan->precio, 2, ',', '.') }}</span>
                                    <span class="cell-primary tabular-nums text-brass-ink">{{ number_format($plan->precioFinal(), 2, ',', '.') }}</span>
                                    <span class="cell-sub">{{ $promocion->motivo }} · − {{ $promocion->etiquetaDescuento() }}</span>
                                @else
                                    <span class="cell-primary tabular-nums">{{ number_format((float) $plan->precio, 2, ',', '.') }}</span>
                                @endif
                                <span class="cell-sub">{{ $plan->periodoPrecioEtiqueta() }}</span>
                            </td>
                            @if ($esStaff)
                                <td>
                                    <x-z.badge variant="accent">{{ $plan->suscripciones_count }} {{ $plan->suscripciones_count === 1 ? 'suscriptor' : 'suscriptores' }}</x-z.badge>
                                </td>
                            @endif
                            <td>
                                <div class="z-row-actions">
                                    @if ($esStaff)
                                        <a href="{{ route('promociones.index', ['plan' => $plan->id]) }}" wire:navigate class="btn btn-ghost btn-sm" title="Crear promoción para este plan">
                                            <x-z.icon name="spark" class="size-4" />
                                            Promoción
                                        </a>
                                    @endif
                                    @if ($suscripcionActiva?->plan_id === $plan->id)
                                        <x-z.badge variant="success">Activa</x-z.badge>
                                        @if ($esStaff)
                                            <button type="button" command="show-modal" commandfor="quitar-plan" class="btn btn-ghost btn-sm text-danger" aria-label="Quitar plan {{ $plan->tipoEtiqueta() }}">
                                                <x-z.icon name="x" class="size-4" />
                                                Quitar
                                            </button>
                                        @endif
                                    @elseif (! $plan->estaActivo())
                                        <span class="z-hint">No disponible</span>
                                    @elseif ($bloqueaCambio)
                                        <x-z.badge variant="neutral">Solo lectura</x-z.badge>
                                        <span class="z-hint">Tu plan activo no se puede cambiar.</span>
                                    @else
                                        <button type="button"
                                                command="show-modal"
                                                commandfor="suscribir-{{ $plan->id }}"
                                                class="btn btn-secondary btn-sm"
                                                aria-label="{{ $plan->esPorDias() ? 'Pedir préstamo de' : 'Confirmar suscripción a' }} {{ $plan->tipoEtiqueta() }}">
                                            <x-z.icon name="check" class="size-4" />
                                            @if ($plan->esPorDias())
                                                Préstamo
                                            @else
                                                {{ $suscripcionActiva ? 'Cambiar a este plan' : 'Suscribirme' }}
                                            @endif
                                        </button>
                                    @endif
                                    @if ($esStaff)
                                        <button type="button" command="show-modal" commandfor="editar-{{ $plan->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $plan->tipoEtiqueta() }}">
                                            <x-z.icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" command="show-modal" commandfor="eliminar-plan-{{ $plan->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $plan->tipoEtiqueta() }}">
                                            <x-z.icon name="trash" class="size-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $esStaff ? 7 : 5 }}">
                                <x-z.vacio titulo="Todavía no hay planes." :copy="$esStaff ? 'Usa «Nuevo plan» para crear el primero.' : 'Vuelve pronto para conocer las opciones de suscripción.'" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach ($planes as $plan)
            @if ($plan->estaActivo() && ! $bloqueaCambio && $suscripcionActiva?->plan_id !== $plan->id)
                <!-- Modal: confirmar suscripción -->
                <dialog id="suscribir-{{ $plan->id }}" class="dialog-box">
                    <div class="dialog-head">
                        <div>
                            <h2 class="dialog-title">{{ $plan->esPorDias() ? 'Confirmar préstamo' : 'Confirmar suscripción' }}</h2>
                            <p class="dialog-copy">{{ $plan->esPorDias() ? 'Revisa el plazo y el precio antes de continuar.' : 'Revisa los datos antes de continuar.' }}</p>
                        </div>
                        <button type="button" command="close" commandfor="suscribir-{{ $plan->id }}" class="z-icon-button" aria-label="Cerrar">
                            <x-z.icon name="x" class="size-4" />
                        </button>
                    </div>

                    <dl class="field-grid two">
                        <div>
                            <dt class="field-label">Usuario</dt>
                            <dd class="text-sm font-medium text-ink">{{ auth()->user()->name }}</dd>
                        </div>
                        <div>
                            <dt class="field-label">Correo</dt>
                            <dd class="text-sm font-medium text-ink">{{ auth()->user()->email }}</dd>
                        </div>
                        <div>
                            <dt class="field-label">Plan</dt>
                            <dd class="text-sm font-medium text-ink">{{ $plan->tipoEtiqueta() }}</dd>
                        </div>
                        <div>
                            <dt class="field-label">Duración</dt>
                            <dd class="text-sm font-medium text-ink">{{ $plan->duracionEtiqueta() }}</dd>
                        </div>
                        <div>
                            <dt class="field-label">Cobertura</dt>
                            <dd class="text-sm font-medium text-ink">
                                {{ $plan->modalidadEtiqueta() }} · {{ $plan->coberturaEtiqueta() }}
                            </dd>
                        </div>
                        <div>
                            <dt class="field-label">Prueba</dt>
                            <dd class="text-sm font-medium text-ink">
                                {{ $plan->tienePrueba() ? $plan->trial_dias.' días' : 'Sin período de prueba' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="field-label">Funcionalidades</dt>
                            <dd class="text-sm font-medium text-ink">
                                @forelse ($plan->beneficiosLista() as $beneficio)
                                    <span class="cell-sub">{{ $beneficio }}</span>
                                @empty
                                    <span class="z-hint">Sin beneficios listados.</span>
                                @endforelse
                            </dd>
                        </div>
                        <div>
                            <dt class="field-label">Precio</dt>
                            <dd class="text-sm font-medium text-ink">
                                <span class="tabular-nums">{{ number_format($plan->precioFinal(), 2, ',', '.') }}</span>
                                <span class="z-hint">{{ $plan->periodoPrecioEtiqueta() }}</span>
                            </dd>
                        </div>
                    </dl>

                    <form action="{{ route('planes.suscribir', $plan) }}" method="POST" class="mt-5">
                        @csrf
                        <input type="hidden" name="_modal" value="suscribir-{{ $plan->id }}">

                        <div class="form-actions">
                            <button type="button" command="close" commandfor="suscribir-{{ $plan->id }}" class="btn btn-secondary">Cancelar</button>
                            <button type="submit" class="btn btn-primary">{{ $plan->esPorDias() ? 'Confirmar préstamo' : 'Confirmar suscripción' }}</button>
                        </div>
                    </form>
                </dialog>
            @endif
        @endforeach
    </div>

    @if ($esStaff && $suscripcionActiva)
        <!-- Modal: quitar plan -->
        <dialog id="quitar-plan" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Quitar plan</h2>
                    <p class="dialog-copy">Tu suscripción dejará de estar activa.</p>
                </div>
                <button type="button" command="close" commandfor="quitar-plan" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres quitar el plan {{ $suscripcionActiva->plan ? $suscripcionActiva->plan->tipoEtiqueta() : 'activo' }}? Podrás suscribirte de nuevo cuando quieras.</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="quitar-plan" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('planes.cancelar', $suscripcionActiva->plan_id) }}" method="POST">
                    @csrf
                    <button type="submit" command="close" commandfor="quitar-plan" class="btn btn-danger">Quitar plan</button>
                </form>
            </div>
        </dialog>
    @endif

    @if ($esStaff)
    <!-- Modal: crear plan -->
    <dialog id="crear-plan" @if ($errors->any() && old('_modal') === 'crear') open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Nuevo plan</h2>
                <p class="dialog-copy">Elige la duración, a qué libros da acceso y los detalles opcionales.</p>
            </div>
            <button type="button" command="close" commandfor="crear-plan" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('planes.partials.errores', ['modal' => 'crear'])

        <form action="{{ route('planes.store') }}" method="POST" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <fieldset class="form-section">
                <legend>Duración y precio</legend>
                <div class="field-grid two">
                    <div>
                        <label for="crear-plan-tipo" class="field-label">Tipo de plan</label>
                        <select id="crear-plan-tipo" name="tipo" data-plan-tipo required class="field">
                            <option value="mensual" data-tipo-general @selected(old('tipo', 'mensual') === 'mensual')>Mensual · 1 mes</option>
                            <option value="anual" data-tipo-general @selected(old('tipo') === 'anual')>Anual · 12 meses</option>
                            <option value="dias" data-tipo-libro @selected(old('tipo') === 'dias')>Por días · elige el plazo</option>
                        </select>
                        <p class="field-help">Catálogo completo y paquetes: mensual o anual. Un libro específico: por días.</p>
                    </div>
                    <div>
                        <label for="crear-plan-precio" class="field-label">Precio</label>
                        <input id="crear-plan-precio" name="precio" type="number" required min="0" max="99999999.99" step="0.01" value="{{ old('precio') }}" class="field tabular-nums" placeholder="Ej. 9.99">
                        <p class="field-help">Por mes, por año o por todo el plazo elegido.</p>
                    </div>
                </div>
                <div data-duracion="dias" class="mt-4">
                    <label for="crear-plan-duracion" class="field-label">Días de acceso al libro</label>
                    <input id="crear-plan-duracion" name="duracion_dias" type="number" min="1" max="365" value="{{ old('duracion_dias', 30) }}" class="field tabular-nums" placeholder="Ej. 15">
                    <p class="field-help">Solo con «Un libro específico». Entre 1 y 365 días.</p>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Cobertura</legend>
                <div>
                    <label for="crear-plan-modalidad" class="field-label">¿A qué libros da acceso?</label>
                    <select id="crear-plan-modalidad" name="modalidad" data-plan-modalidad required class="field">
                        <option value="general" @selected(old('modalidad', 'general') === 'general')>Catálogo completo</option>
                        <option value="libro" @selected(old('modalidad') === 'libro')>Un libro específico</option>
                        <option value="paquete" @selected(old('modalidad') === 'paquete')>Paquete de libros</option>
                    </select>
                    <p class="field-help">Puedes tener a la vez un plan general, uno por libro y otro por paquete. Solo «Un libro específico» se paga por días.</p>
                </div>

                <div data-cobertura="libro" class="mt-4">
                    <label for="crear-plan-libro" class="field-label">Libro incluido</label>
                    <select id="crear-plan-libro" name="libro_id" class="field">
                        <option value="">Selecciona un libro</option>
                        @foreach ($librosDisponibles as $libroDisponible)
                            <option value="{{ $libroDisponible->id }}" @selected((string) old('libro_id') === (string) $libroDisponible->id)>{{ $libroDisponible->titulo }}</option>
                        @endforeach
                    </select>
                    <p class="field-help">Solo los suscriptores de este plan podrán leerlo.</p>
                </div>

                <div data-cobertura="paquete" class="mt-4">
                    <label for="crear-plan-libros" class="field-label">Libros del paquete</label>
                    <select id="crear-plan-libros" name="libros[]" multiple class="field" size="5">
                        @foreach ($librosDisponibles as $libroDisponible)
                            <option value="{{ $libroDisponible->id }}" @selected(in_array((string) $libroDisponible->id, array_map('strval', old('libros', []))))>{{ $libroDisponible->titulo }}</option>
                        @endforeach
                    </select>
                    <p class="field-help">Mantén pulsado Ctrl/Cmd para elegir varios libros.</p>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Detalles opcionales</legend>
                <div class="field-grid two">
                    <div>
                        <label for="crear-plan-trial" class="field-label">Días de prueba</label>
                        <input id="crear-plan-trial" name="trial_dias" type="number" min="0" max="365" value="{{ old('trial_dias') }}" class="field tabular-nums" placeholder="Ej. 7">
                        <p class="field-help">0 o vacío = sin prueba.</p>
                    </div>
                    <div>
                        <label for="crear-plan-beneficios" class="field-label">Beneficios</label>
                        <textarea id="crear-plan-beneficios" name="beneficios" rows="3" maxlength="1000" class="field" placeholder="Lectura ilimitada&#10;Reseñas y favoritos">{{ old('beneficios') }}</textarea>
                        <p class="field-help">Un beneficio por línea.</p>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-plan" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-plan" class="btn btn-primary">Crear plan</button>
            </div>
        </form>
    </dialog>

    @foreach ($planes as $plan)
        <!-- Modal: editar plan -->
        @php $modal = 'editar-'.$plan->id; @endphp
        <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Editar plan</h2>
                    <p class="dialog-copy">Plan {{ $plan->tipoEtiqueta() }} · {{ $plan->modalidadEtiqueta() }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('planes.partials.errores', ['modal' => $modal])

            <form action="{{ route('planes.update', $plan) }}" method="POST" class="field-grid">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="{{ $modal }}">

                <fieldset class="form-section">
                    <legend>Duración y precio</legend>
                    <div class="field-grid two">
                        <div>
                            <label for="editar-plan-tipo-{{ $plan->id }}" class="field-label">Tipo de plan</label>
                            <select id="editar-plan-tipo-{{ $plan->id }}" name="tipo" data-plan-tipo required class="field">
                                <option value="mensual" data-tipo-general @selected(old('tipo', $plan->tipo) === 'mensual')>Mensual · 1 mes</option>
                                <option value="anual" data-tipo-general @selected(old('tipo', $plan->tipo) === 'anual')>Anual · 12 meses</option>
                                <option value="dias" data-tipo-libro @selected(old('tipo', $plan->tipo) === 'dias')>Por días · elige el plazo</option>
                            </select>
                            <p class="field-help">Catálogo completo y paquetes: mensual o anual. Un libro específico: por días.</p>
                        </div>
                        <div>
                            <label for="editar-plan-precio-{{ $plan->id }}" class="field-label">Precio</label>
                            <input id="editar-plan-precio-{{ $plan->id }}" name="precio" type="number" required min="0" max="99999999.99" step="0.01" value="{{ old('precio', $plan->precio) }}" class="field tabular-nums">
                            <p class="field-help">Por mes, por año o por todo el plazo elegido.</p>
                        </div>
                    </div>
                    <div data-duracion="dias" class="mt-4">
                        <label for="editar-plan-duracion-{{ $plan->id }}" class="field-label">Días de acceso al libro</label>
                        <input id="editar-plan-duracion-{{ $plan->id }}" name="duracion_dias" type="number" min="1" max="365" value="{{ old('duracion_dias', $plan->duracion_dias ?? 30) }}" class="field tabular-nums" placeholder="Ej. 15">
                        <p class="field-help">Solo con «Un libro específico». Entre 1 y 365 días.</p>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Cobertura</legend>
                    <div>
                        <label for="editar-plan-modalidad-{{ $plan->id }}" class="field-label">¿A qué libros da acceso?</label>
                        <select id="editar-plan-modalidad-{{ $plan->id }}" name="modalidad" data-plan-modalidad required class="field">
                            <option value="general" @selected(old('modalidad', $plan->modalidad) === 'general')>Catálogo completo</option>
                            <option value="libro" @selected(old('modalidad', $plan->modalidad) === 'libro')>Un libro específico</option>
                            <option value="paquete" @selected(old('modalidad', $plan->modalidad) === 'paquete')>Paquete de libros</option>
                        </select>
                        <p class="field-help">Puedes tener a la vez un plan general, uno por libro y otro por paquete. Solo «Un libro específico» se paga por días.</p>
                    </div>

                    <div data-cobertura="libro" class="mt-4">
                        <label for="editar-plan-libro-{{ $plan->id }}" class="field-label">Libro incluido</label>
                        <select id="editar-plan-libro-{{ $plan->id }}" name="libro_id" class="field">
                            <option value="">Selecciona un libro</option>
                            @foreach ($librosDisponibles as $libroDisponible)
                                <option value="{{ $libroDisponible->id }}" @selected((string) old('libro_id', $plan->libro_id) === (string) $libroDisponible->id)>{{ $libroDisponible->titulo }}</option>
                            @endforeach
                        </select>
                        <p class="field-help">Solo los suscriptores de este plan podrán leerlo.</p>
                    </div>

                    <div data-cobertura="paquete" class="mt-4">
                        <label for="editar-plan-libros-{{ $plan->id }}" class="field-label">Libros del paquete</label>
                        <select id="editar-plan-libros-{{ $plan->id }}" name="libros[]" multiple class="field" size="5">
                            @php $librosSeleccionados = array_map('strval', old('libros', $plan->libros->pluck('libro_id')->all())); @endphp
                            @foreach ($librosDisponibles as $libroDisponible)
                                <option value="{{ $libroDisponible->id }}" @selected(in_array((string) $libroDisponible->id, $librosSeleccionados))>{{ $libroDisponible->titulo }}</option>
                            @endforeach
                        </select>
                        <p class="field-help">Mantén pulsado Ctrl/Cmd para elegir varios libros.</p>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <legend>Detalles opcionales</legend>
                    <div class="field-grid two">
                        <div>
                            <label for="editar-plan-trial-{{ $plan->id }}" class="field-label">Días de prueba</label>
                            <input id="editar-plan-trial-{{ $plan->id }}" name="trial_dias" type="number" min="0" max="365" value="{{ old('trial_dias', $plan->trial_dias) }}" class="field tabular-nums" placeholder="Ej. 7">
                            <p class="field-help">0 o vacío = sin prueba.</p>
                        </div>
                        <div>
                            <label for="editar-plan-estado-{{ $plan->id }}" class="field-label">Estado</label>
                            <select id="editar-plan-estado-{{ $plan->id }}" name="estado" required class="field">
                                <option value="activa" @selected(old('estado', $plan->estado) === 'activa')>Activa · visible para clientes</option>
                                <option value="inactiva" @selected(old('estado', $plan->estado) === 'inactiva')>Inactiva · oculta</option>
                            </select>
                            <p class="field-help">Al activarlo solo se inactiva otro plan con idéntica cobertura.</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="editar-plan-beneficios-{{ $plan->id }}" class="field-label">Beneficios</label>
                        <textarea id="editar-plan-beneficios-{{ $plan->id }}" name="beneficios" rows="3" maxlength="1000" class="field" placeholder="Lectura ilimitada&#10;Reseñas y favoritos">{{ old('beneficios', implode("\n", $plan->beneficiosLista())) }}</textarea>
                        <p class="field-help">Un beneficio por línea.</p>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: confirmar eliminación -->
        <dialog id="eliminar-plan-{{ $plan->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Eliminar plan</h2>
                    <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                </div>
                <button type="button" command="close" commandfor="eliminar-plan-{{ $plan->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres eliminar «{{ $plan->tipoEtiqueta() }}»? También se eliminarán sus suscripciones.</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="eliminar-plan-{{ $plan->id }}" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('planes.destroy', $plan) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" command="close" commandfor="eliminar-plan-{{ $plan->id }}" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </dialog>
    @endforeach

    @endif

    @if ($esStaff)
        <script>
            function actualizarFormularioPlan(modalidad) {
                const formulario = modalidad.closest('form');
                if (! formulario) {
                    return;
                }

                const porLibro = modalidad.value === 'libro';
                const tipo = formulario.querySelector('[data-plan-tipo]');

                if (tipo) {
                    tipo.querySelectorAll('[data-tipo-libro]').forEach((opcion) => {
                        opcion.hidden = ! porLibro;
                        opcion.disabled = ! porLibro;
                    });
                    tipo.querySelectorAll('[data-tipo-general]').forEach((opcion) => {
                        opcion.hidden = porLibro;
                        opcion.disabled = porLibro;
                    });

                    if (porLibro) {
                        tipo.value = 'dias';
                    } else if (tipo.value === 'dias') {
                        tipo.value = 'mensual';
                    }
                }

                formulario.querySelectorAll('[data-duracion]').forEach((bloque) => {
                    bloque.hidden = ! porLibro;
                    bloque.querySelectorAll('input').forEach((campo) => {
                        campo.disabled = ! porLibro;
                        campo.required = porLibro;
                    });
                });

                formulario.querySelectorAll('[data-cobertura]').forEach((bloque) => {
                    const visible = bloque.dataset.cobertura === modalidad.value;
                    bloque.hidden = ! visible;
                    bloque.querySelectorAll('select').forEach((campo) => {
                        campo.disabled = ! visible;
                        campo.required = visible || campo.multiple;
                    });
                });
            }

            document.querySelectorAll('[data-plan-modalidad]').forEach((modalidad) => {
                modalidad.addEventListener('change', () => actualizarFormularioPlan(modalidad));
                actualizarFormularioPlan(modalidad);
            });
        </script>
    @endif

    @include('partials.modal-scripts')
</x-layouts::app>

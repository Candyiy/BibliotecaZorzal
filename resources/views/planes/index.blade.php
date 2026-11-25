<x-layouts::app :title="__('Planes')">
    @php
        $esStaff = auth()->user()->esStaff();
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
                    <p class="z-card-title">Tu suscripción activa</p>
                    <p class="z-card-note">
                        Plan {{ $suscripcionActiva->plan ? ucfirst($suscripcionActiva->plan->tipo) : '—' }} ·
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
                    <button type="button" command="show-modal" commandfor="quitar-plan" class="btn btn-ghost btn-sm text-danger">
                        <x-z.icon name="x" class="size-4" />
                        Quitar plan
                    </button>
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
                                <span class="cell-primary">{{ ucfirst($plan->tipo) }}</span>
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
                                <span class="cell-sub">{{ $plan->esAnual() ? 'por año' : 'por mes' }}</span>
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
                                        <button type="button" command="show-modal" commandfor="quitar-plan" class="btn btn-ghost btn-sm text-danger" aria-label="Quitar plan {{ $plan->tipo }}">
                                            <x-z.icon name="x" class="size-4" />
                                            Quitar
                                        </button>
                                    @elseif (! $plan->estaActivo())
                                        <span class="z-hint">No disponible</span>
                                    @else
                                        <button type="button"
                                                command="show-modal"
                                                commandfor="suscribir-{{ $plan->id }}"
                                                class="btn btn-secondary btn-sm"
                                                aria-label="Confirmar suscripción a {{ $plan->tipo }}">
                                            <x-z.icon name="check" class="size-4" />
                                            {{ $suscripcionActiva ? 'Cambiar a este plan' : 'Suscribirme' }}
                                        </button>
                                    @endif
                                    @if ($esStaff)
                                        <button type="button" command="show-modal" commandfor="editar-{{ $plan->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $plan->tipo }}">
                                            <x-z.icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" command="show-modal" commandfor="eliminar-plan-{{ $plan->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $plan->tipo }}">
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
            @if ($plan->estaActivo() && $suscripcionActiva?->plan_id !== $plan->id)
                <!-- Modal: confirmar suscripción -->
                <dialog id="suscribir-{{ $plan->id }}" class="dialog-box">
                    <div class="dialog-head">
                        <div>
                            <h2 class="dialog-title">Confirmar suscripción</h2>
                            <p class="dialog-copy">Revisa los datos antes de continuar.</p>
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
                            <dd class="text-sm font-medium text-ink">{{ ucfirst($plan->tipo) }}</dd>
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
                                <span class="z-hint">{{ $plan->esAnual() ? 'por año' : 'por mes' }}</span>
                            </dd>
                        </div>
                    </dl>

                    <form action="{{ route('planes.suscribir', $plan) }}" method="POST" class="mt-5">
                        @csrf
                        <input type="hidden" name="_modal" value="suscribir-{{ $plan->id }}">

                        <div class="form-actions">
                            <button type="button" command="close" commandfor="suscribir-{{ $plan->id }}" class="btn btn-secondary">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Confirmar suscripción</button>
                        </div>
                    </form>
                </dialog>
            @endif
        @endforeach
    </div>

    @if ($suscripcionActiva)
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

            <p class="dialog-copy">¿Seguro que quieres quitar el plan {{ $suscripcionActiva->plan ? ucfirst($suscripcionActiva->plan->tipo) : 'activo' }}? Podrás suscribirte de nuevo cuando quieras.</p>

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
                <p class="dialog-copy">Define tipo, cobertura, prueba y funcionalidades. El plan anterior del mismo tipo quedará inactivo.</p>
            </div>
            <button type="button" command="close" commandfor="crear-plan" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('planes.partials.errores', ['modal' => 'crear'])

        <form action="{{ route('planes.store') }}" method="POST" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <div>
                <label for="crear-plan-tipo" class="field-label">Tipo</label>
                <select id="crear-plan-tipo" name="tipo" required class="field">
                    <option value="mensual" @selected(old('tipo') === 'mensual')>Mensual</option>
                    <option value="anual" @selected(old('tipo') === 'anual')>Anual</option>
                </select>
            </div>

            <div>
                <label for="crear-plan-modalidad" class="field-label">Cobertura</label>
                <select id="crear-plan-modalidad" name="modalidad" required class="field">
                    <option value="general" @selected(old('modalidad', 'general') === 'general')>Catálogo completo</option>
                    <option value="libro" @selected(old('modalidad') === 'libro')>Por libro</option>
                    <option value="paquete" @selected(old('modalidad') === 'paquete')>Paquete de libros</option>
                </select>
            </div>

            <div>
                <label for="crear-plan-libro" class="field-label">Libro (solo «Por libro»)</label>
                <select id="crear-plan-libro" name="libro_id" class="field">
                    <option value="">Selecciona un libro</option>
                    @foreach ($librosDisponibles as $libroDisponible)
                        <option value="{{ $libroDisponible->id }}" @selected((string) old('libro_id') === (string) $libroDisponible->id)>{{ $libroDisponible->titulo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="crear-plan-libros" class="field-label">Libros del paquete</label>
                <select id="crear-plan-libros" name="libros[]" multiple class="field" size="5">
                    @foreach ($librosDisponibles as $libroDisponible)
                        <option value="{{ $libroDisponible->id }}" @selected(in_array((string) $libroDisponible->id, array_map('strval', old('libros', []))))>{{ $libroDisponible->titulo }}</option>
                    @endforeach
                </select>
                <p class="field-help">Selecciona varios con Ctrl/Cmd. Solo se usa con «Paquete de libros».</p>
            </div>

            <div>
                <label for="crear-plan-trial" class="field-label">Días de prueba</label>
                <input id="crear-plan-trial" name="trial_dias" type="number" min="0" max="365" value="{{ old('trial_dias') }}" class="field tabular-nums" placeholder="Ej. 7">
                <p class="field-help">Déjalo vacío para no ofrecer prueba.</p>
            </div>

            <div>
                <label for="crear-plan-beneficios" class="field-label">Funcionalidades (una por línea)</label>
                <textarea id="crear-plan-beneficios" name="beneficios" rows="3" maxlength="1000" class="field" placeholder="Lectura ilimitada&#10;Reseñas y favoritos">{{ old('beneficios') }}</textarea>
            </div>

            <div>
                <label for="crear-plan-precio" class="field-label">Precio</label>
                <input id="crear-plan-precio" name="precio" type="number" required min="0" max="99999999.99" step="0.01" value="{{ old('precio') }}" class="field tabular-nums" placeholder="Ej. 9.99">
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-plan" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-plan" class="btn btn-primary">Guardar plan</button>
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
                    <p class="dialog-copy">{{ $plan->tipo }}</p>
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

                <div>
                    <label for="editar-plan-tipo-{{ $plan->id }}" class="field-label">Tipo</label>
                    <select id="editar-plan-tipo-{{ $plan->id }}" name="tipo" required class="field">
                        <option value="mensual" @selected(old('tipo', $plan->tipo) === 'mensual')>Mensual</option>
                        <option value="anual" @selected(old('tipo', $plan->tipo) === 'anual')>Anual</option>
                    </select>
                </div>

                <div>
                    <label for="editar-plan-modalidad-{{ $plan->id }}" class="field-label">Cobertura</label>
                <select id="editar-plan-modalidad-{{ $plan->id }}" name="modalidad" required class="field">
                    <option value="general" @selected(old('modalidad', $plan->modalidad) === 'general')>Catálogo completo</option>
                    <option value="libro" @selected(old('modalidad', $plan->modalidad) === 'libro')>Por libro</option>
                    <option value="paquete" @selected(old('modalidad', $plan->modalidad) === 'paquete')>Paquete de libros</option>
                </select>
            </div>

            <div>
                <label for="editar-plan-libro-{{ $plan->id }}" class="field-label">Libro (solo «Por libro»)</label>
                <select id="editar-plan-libro-{{ $plan->id }}" name="libro_id" class="field">
                    <option value="">Selecciona un libro</option>
                    @foreach ($librosDisponibles as $libroDisponible)
                        <option value="{{ $libroDisponible->id }}" @selected((string) old('libro_id', $plan->libro_id) === (string) $libroDisponible->id)>{{ $libroDisponible->titulo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="editar-plan-libros-{{ $plan->id }}" class="field-label">Libros del paquete</label>
                <select id="editar-plan-libros-{{ $plan->id }}" name="libros[]" multiple class="field" size="5">
                    @php $librosSeleccionados = array_map('strval', old('libros', $plan->libros->pluck('id')->all())); @endphp
                    @foreach ($librosDisponibles as $libroDisponible)
                        <option value="{{ $libroDisponible->id }}" @selected(in_array((string) $libroDisponible->id, $librosSeleccionados))>{{ $libroDisponible->titulo }}</option>
                    @endforeach
                </select>
                <p class="field-help">Solo se usa con «Paquete de libros».</p>
            </div>

            <div>
                <label for="editar-plan-trial-{{ $plan->id }}" class="field-label">Días de prueba</label>
                <input id="editar-plan-trial-{{ $plan->id }}" name="trial_dias" type="number" min="0" max="365" value="{{ old('trial_dias', $plan->trial_dias) }}" class="field tabular-nums">
            </div>

            <div>
                <label for="editar-plan-beneficios-{{ $plan->id }}" class="field-label">Funcionalidades (una por línea)</label>
                <textarea id="editar-plan-beneficios-{{ $plan->id }}" name="beneficios" rows="3" maxlength="1000" class="field">{{ old('beneficios', implode("\n", $plan->beneficiosLista())) }}</textarea>
            </div>

            <div>
                <label for="editar-plan-estado-{{ $plan->id }}" class="field-label">Estado</label>
                    <select id="editar-plan-estado-{{ $plan->id }}" name="estado" required class="field">
                        <option value="activa" @selected(old('estado', $plan->estado) === 'activa')>Activa</option>
                        <option value="inactiva" @selected(old('estado', $plan->estado) === 'inactiva')>Inactiva</option>
                    </select>
                    <p class="field-help">Solo puede haber un plan activo por tipo.</p>
                </div>

                <div>
                    <label for="editar-plan-precio-{{ $plan->id }}" class="field-label">Precio</label>
                    <input id="editar-plan-precio-{{ $plan->id }}" name="precio" type="number" required min="0" max="99999999.99" step="0.01" value="{{ old('precio', $plan->precio) }}" class="field tabular-nums">
                </div>

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

            <p class="dialog-copy">¿Seguro que quieres eliminar «{{ $plan->tipo }}»? También se eliminarán sus suscripciones.</p>

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

    @include('partials.modal-scripts')
</x-layouts::app>

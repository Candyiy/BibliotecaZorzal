<x-layouts::app :title="__('Promociones')">
    <div class="z-page">
        <x-z.page-header title="Promociones" subtitle="Crea descuentos y aplícalos a los planes al suscribirse.">
            <x-slot name="actions">
                <button type="button" command="show-modal" commandfor="crear-promocion" class="btn btn-primary">
                    <x-z.icon name="plus" class="size-4" />
                    Nueva promoción
                </button>
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por motivo, estado o plan..." :resultados="$promociones->count()" />

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Motivo</th>
                        <th scope="col">Descuento</th>
                        <th scope="col">Vigencia</th>
                        <th scope="col">Planes</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promociones as $promocion)
                        <tr>
                            <td><span class="cell-primary">{{ $promocion->motivo }}</span></td>
                            <td>
                                <span class="cell-primary tabular-nums">− {{ $promocion->etiquetaDescuento() }}</span>
                            </td>
                            <td>
                                <span class="cell-primary">{{ $promocion->f_inicio?->format('d/m/Y') }} — {{ $promocion->f_finalizacion?->format('d/m/Y') }}</span>
                            </td>
                            <td>
                                <span class="cell-primary">{{ $promocion->planes->pluck('tipo')->map(fn ($tipo) => ucfirst($tipo))->join(', ') }}</span>
                                <span class="cell-sub">{{ $promocion->planes_count }} {{ $promocion->planes_count === 1 ? 'plan' : 'planes' }}</span>
                            </td>
                            <td>
                                <x-z.badge variant="{{ $promocion->esVigente() ? 'success' : 'warning' }}">
                                    {{ $promocion->esVigente() ? 'Vigente' : ($promocion->estado === 'activa' ? 'Fuera de vigencia' : 'Inactiva') }}
                                </x-z.badge>
                            </td>
                            <td>
                                <div class="z-row-actions">
                                    <button type="button" command="show-modal" commandfor="editar-{{ $promocion->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $promocion->motivo }}">
                                        <x-z.icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" command="show-modal" commandfor="eliminar-promocion-{{ $promocion->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $promocion->motivo }}">
                                        <x-z.icon name="trash" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-z.vacio titulo="Todavía no hay promociones." copy="Usa «Nueva promoción» para crear el primer descuento." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: crear promoción -->
    <dialog id="crear-promocion" @if (($errors->any() && old('_modal') === 'crear') || $planPreseleccionado) open @endif class="dialog-box">
        <div class="dialog-head">
            <div>
                <h2 class="dialog-title">Nueva promoción</h2>
                <p class="dialog-copy">
                    @if ($planPreseleccionado)
                        Crea un descuento para el plan {{ ucfirst($planPreseleccionado->tipo) }}.
                    @else
                        Define el porcentaje de descuento y los planes a los que se aplica.
                    @endif
                </p>
            </div>
            <button type="button" command="close" commandfor="crear-promocion" class="z-icon-button" aria-label="Cerrar">
                <x-z.icon name="x" class="size-4" />
            </button>
        </div>

        @include('promociones.partials.errores', ['modal' => 'crear'])

        <form action="{{ route('promociones.store') }}" method="POST" class="field-grid">
            @csrf
            <input type="hidden" name="_modal" value="crear">

            <div>
                <label for="crear-promocion-motivo" class="field-label">Motivo</label>
                <input id="crear-promocion-motivo" name="motivo" type="text" required maxlength="255" value="{{ old('motivo') }}" class="field" placeholder="Ej. Bienvenida">
            </div>

            <div class="field-grid two">
                <div>
                    <label for="crear-promocion-cuanto" class="field-label">Descuento (%)</label>
                    <input id="crear-promocion-cuanto" name="cuanto" type="number" required min="0.01" max="100" step="0.01" value="{{ old('cuanto') }}" class="field tabular-nums" placeholder="Ej. 20">
                    <p class="field-help">Porcentaje del precio del plan (hasta 100).</p>
                </div>

                <div>
                    <label for="crear-promocion-estado" class="field-label">Estado</label>
                    <select id="crear-promocion-estado" name="estado" required class="field">
                        <option value="activa" @selected(old('estado') === 'activa')>Activa</option>
                        <option value="inactiva" @selected(old('estado') === 'inactiva')>Inactiva</option>
                    </select>
                </div>
            </div>

            <div class="field-grid two">
                <div>
                    <label for="crear-promocion-inicio" class="field-label">Fecha de inicio</label>
                    <input id="crear-promocion-inicio" name="f_inicio" type="date" required value="{{ old('f_inicio', today()->toDateString()) }}" class="field">
                </div>

                <div>
                    <label for="crear-promocion-final" class="field-label">Fecha de finalización</label>
                    <input id="crear-promocion-final" name="f_finalizacion" type="date" required value="{{ old('f_finalizacion', today()->addMonth()->toDateString()) }}" class="field">
                </div>
            </div>

            <div>
                <label for="crear-promocion-planes" class="field-label">Planes</label>
                <select id="crear-promocion-planes" name="planes[]" multiple size="4" required class="field">
                    @php $seleccionados = old('planes', $planPreseleccionado ? [$planPreseleccionado->id] : []); @endphp
                    @foreach ($planes as $plan)
                        <option value="{{ $plan->id }}" @selected(in_array($plan->id, $seleccionados))>{{ ucfirst($plan->tipo) }} — {{ number_format((float) $plan->precio, 2, ',', '.') }}</option>
                    @endforeach
                </select>
                <p class="field-help">Selecciona al menos un plan (Ctrl/Command + clic para varios).</p>
            </div>

            <div class="form-actions">
                <button type="button" command="close" commandfor="crear-promocion" class="btn btn-secondary">Cancelar</button>
                <button type="submit" command="close" commandfor="crear-promocion" class="btn btn-primary">Guardar promoción</button>
            </div>
        </form>
    </dialog>

    @foreach ($promociones as $promocion)
        <!-- Modal: editar promoción -->
        @php $modal = 'editar-'.$promocion->id; @endphp
        <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Editar promoción</h2>
                    <p class="dialog-copy">{{ $promocion->motivo }}</p>
                </div>
                <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            @include('promociones.partials.errores', ['modal' => $modal])

            <form action="{{ route('promociones.update', $promocion) }}" method="POST" class="field-grid">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="{{ $modal }}">

                <div>
                    <label for="editar-promocion-motivo-{{ $promocion->id }}" class="field-label">Motivo</label>
                    <input id="editar-promocion-motivo-{{ $promocion->id }}" name="motivo" type="text" required maxlength="255" value="{{ old('motivo', $promocion->motivo) }}" class="field">
                </div>

                <div class="field-grid two">
                    <div>
                        <label for="editar-promocion-cuanto-{{ $promocion->id }}" class="field-label">Descuento (%)</label>
                        <input id="editar-promocion-cuanto-{{ $promocion->id }}" name="cuanto" type="number" required min="0.01" max="100" step="0.01" value="{{ old('cuanto', $promocion->cuanto) }}" class="field tabular-nums">
                        <p class="field-help">Porcentaje del precio del plan (hasta 100).</p>
                    </div>

                    <div>
                        <label for="editar-promocion-estado-{{ $promocion->id }}" class="field-label">Estado</label>
                        <select id="editar-promocion-estado-{{ $promocion->id }}" name="estado" required class="field">
                            <option value="activa" @selected(old('estado', $promocion->estado) === 'activa')>Activa</option>
                            <option value="inactiva" @selected(old('estado', $promocion->estado) === 'inactiva')>Inactiva</option>
                        </select>
                    </div>
                </div>

                <div class="field-grid two">
                    <div>
                        <label for="editar-promocion-inicio-{{ $promocion->id }}" class="field-label">Fecha de inicio</label>
                        <input id="editar-promocion-inicio-{{ $promocion->id }}" name="f_inicio" type="date" required value="{{ old('f_inicio', $promocion->f_inicio?->format('Y-m-d')) }}" class="field">
                    </div>

                    <div>
                        <label for="editar-promocion-final-{{ $promocion->id }}" class="field-label">Fecha de finalización</label>
                        <input id="editar-promocion-final-{{ $promocion->id }}" name="f_finalizacion" type="date" required value="{{ old('f_finalizacion', $promocion->f_finalizacion?->format('Y-m-d')) }}" class="field">
                    </div>
                </div>

                <div>
                    <label for="editar-promocion-planes-{{ $promocion->id }}" class="field-label">Planes</label>
                    <select id="editar-promocion-planes-{{ $promocion->id }}" name="planes[]" multiple size="4" required class="field">
                        @php $seleccionados = old('planes', $promocion->planes->pluck('id')->all()); @endphp
                        @foreach ($planes as $plan)
                            <option value="{{ $plan->id }}" @selected(in_array($plan->id, $seleccionados))>{{ ucfirst($plan->tipo) }} — {{ number_format((float) $plan->precio, 2, ',', '.') }}</option>
                        @endforeach
                    </select>
                    <p class="field-help">Selecciona al menos un plan (Ctrl/Command + clic para varios).</p>
                </div>

                <div class="form-actions">
                    <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </dialog>

        <!-- Modal: confirmar eliminación -->
        <dialog id="eliminar-promocion-{{ $promocion->id }}" class="dialog-box">
            <div class="dialog-head">
                <div>
                    <h2 class="dialog-title">Eliminar promoción</h2>
                    <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                </div>
                <button type="button" command="close" commandfor="eliminar-promocion-{{ $promocion->id }}" class="z-icon-button" aria-label="Cerrar">
                    <x-z.icon name="x" class="size-4" />
                </button>
            </div>

            <p class="dialog-copy">¿Seguro que quieres eliminar «{{ $promocion->motivo }}»? El descuento dejará de aplicarse a sus planes.</p>

            <div class="form-actions mt-6">
                <button type="button" command="close" commandfor="eliminar-promocion-{{ $promocion->id }}" class="btn btn-secondary">Cancelar</button>
                <form action="{{ route('promociones.destroy', $promocion) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" command="close" commandfor="eliminar-promocion-{{ $promocion->id }}" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </dialog>
    @endforeach

    @include('partials.modal-scripts')
</x-layouts::app>

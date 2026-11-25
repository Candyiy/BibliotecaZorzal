<x-layouts::app :title="__('Usuarios')">
    <div class="z-page">
        <x-z.page-header title="Usuarios" subtitle="Gestiona las cuentas que tienen acceso a la biblioteca.">
            <x-slot name="actions">
                <div class="z-row-actions">
                    <button type="button" command="show-modal" commandfor="crear-admin" class="btn btn-primary">
                        <x-z.icon name="plus" class="size-4" />
                        Nuevo administrador
                    </button>
                    <button type="button" command="show-modal" commandfor="crear-cliente" class="btn btn-secondary">
                        <x-z.icon name="plus" class="size-4" />
                        Nuevo cliente
                    </button>
                </div>
            </x-slot>
        </x-z.page-header>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <x-z.buscador placeholder="Buscar por nombre, correo o rol..." :resultados="$usuarios->total()" />

        <div class="z-table-wrap">
            <table class="z-table">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Correo electrónico</th>
                        <th scope="col">Suscripción</th>
                        <th scope="col">Registro</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td>
                                <span class="cell-primary">{{ $usuario->name }}</span>
                                <span class="cell-sub">{{ $usuario->lecturas_count }} {{ $usuario->lecturas_count === 1 ? 'lectura' : 'lecturas' }}</span>
                                @if ($usuario->estaSuspendido())
                                    <x-z.badge variant="danger">Suspendido</x-z.badge>
                                @endif
                            </td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                @if ($usuario->suscripcionActiva?->plan)
                                    <x-z.badge variant="accent">{{ ucfirst($usuario->suscripcionActiva->plan->tipo) }}</x-z.badge>
                                @else
                                    <span class="cell-sub">Sin plan</span>
                                @endif
                            </td>
                            <td>{{ $usuario->created_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <div class="z-row-actions">
                                    @if ($usuario->id === auth()->id())
                                        <x-z.badge variant="info">Tu cuenta</x-z.badge>
                                    @else
                                        @if ($usuario->estaSuspendido())
                                            <button type="button" command="show-modal" commandfor="activar-usuario-{{ $usuario->id }}" class="btn btn-ghost btn-sm" aria-label="Reactivar {{ $usuario->name }}">
                                                Reactivar
                                            </button>
                                        @else
                                            <button type="button" command="show-modal" commandfor="suspender-usuario-{{ $usuario->id }}" class="btn btn-ghost btn-sm" aria-label="Suspender {{ $usuario->name }}">
                                                Suspender
                                            </button>
                                        @endif
                                        <button type="button" command="show-modal" commandfor="editar-{{ $usuario->id }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Editar {{ $usuario->name }}">
                                            <x-z.icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" command="show-modal" commandfor="eliminar-usuario-{{ $usuario->id }}" class="btn btn-ghost btn-sm btn-icon text-danger" aria-label="Eliminar {{ $usuario->name }}">
                                            <x-z.icon name="trash" class="size-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-z.vacio titulo="Todavía no hay usuarios." copy="Usa «Nuevo administrador» o «Nuevo cliente» para crear la primera cuenta." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $usuarios->links() }}
    </div>

    @include('usuarios.partials.crear-form', ['rol' => \App\Models\User::ROL_ADMIN])
    @include('usuarios.partials.crear-form', ['rol' => \App\Models\User::ROL_CLIENTE])

    @foreach ($usuarios as $usuario)
        @if ($usuario->id !== auth()->id())
            <!-- Modal: suspender usuario -->
            @php $modalSuspender = 'suspender-usuario-'.$usuario->id; @endphp
            <dialog id="{{ $modalSuspender }}" @if ($errors->any() && old('_modal') === $modalSuspender) open @endif class="dialog-box">
                <div class="dialog-head">
                    <div>
                        <h2 class="dialog-title">Suspender cuenta</h2>
                        <p class="dialog-copy">{{ $usuario->name }}</p>
                    </div>
                    <button type="button" command="close" commandfor="{{ $modalSuspender }}" class="z-icon-button" aria-label="Cerrar">
                        <x-z.icon name="x" class="size-4" />
                    </button>
                </div>

                @include('usuarios.partials.errores', ['modal' => $modalSuspender])

                <form action="{{ route('usuarios.suspender', $usuario) }}" method="POST" class="field-grid">
                    @csrf
                    <input type="hidden" name="_modal" value="{{ $modalSuspender }}">

                    <div>
                        <label for="dias-suspension-{{ $usuario->id }}" class="field-label">Días de suspensión</label>
                        <input id="dias-suspension-{{ $usuario->id }}" name="dias" type="number" min="1" max="365" required value="{{ old('dias', 7) }}" class="field tabular-nums">
                        <p class="field-help">La cuenta se reactivará automáticamente cuando termine el plazo.</p>
                    </div>

                    <div class="form-actions">
                        <button type="button" command="close" commandfor="{{ $modalSuspender }}" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" command="close" commandfor="{{ $modalSuspender }}" class="btn btn-danger">Suspender</button>
                    </div>
                </form>
            </dialog>

            <!-- Modal: reactivar usuario -->
            <dialog id="activar-usuario-{{ $usuario->id }}" class="dialog-box">
                <div class="dialog-head">
                    <div>
                        <h2 class="dialog-title">Reactivar cuenta</h2>
                        <p class="dialog-copy">{{ $usuario->name }}</p>
                    </div>
                    <button type="button" command="close" commandfor="activar-usuario-{{ $usuario->id }}" class="z-icon-button" aria-label="Cerrar">
                        <x-z.icon name="x" class="size-4" />
                    </button>
                </div>

                <p class="dialog-copy">¿Quieres restaurar el acceso de «{{ $usuario->name }}» ahora mismo?</p>

                <div class="form-actions mt-6">
                    <button type="button" command="close" commandfor="activar-usuario-{{ $usuario->id }}" class="btn btn-secondary">Cancelar</button>
                    <form action="{{ route('usuarios.activar', $usuario) }}" method="POST">
                        @csrf
                        <button type="submit" command="close" commandfor="activar-usuario-{{ $usuario->id }}" class="btn btn-primary">Reactivar</button>
                    </form>
                </div>
            </dialog>

            <!-- Modal: editar usuario -->
            @php $modal = 'editar-'.$usuario->id; @endphp
            <dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
                <div class="dialog-head">
                    <div>
                        <h2 class="dialog-title">Editar usuario</h2>
                        <p class="dialog-copy">{{ $usuario->name }}</p>
                    </div>
                    <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
                        <x-z.icon name="x" class="size-4" />
                    </button>
                </div>

                @include('usuarios.partials.errores', ['modal' => $modal])

                <form action="{{ route('usuarios.update', $usuario) }}" method="POST" class="field-grid">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="{{ $modal }}">

                    <div>
                        <label for="editar-usuario-nombre-{{ $usuario->id }}" class="field-label">Nombre</label>
                        <input id="editar-usuario-nombre-{{ $usuario->id }}" name="name" type="text" required maxlength="255" value="{{ old('name', $usuario->name) }}" class="field">
                    </div>

                    <div>
                        <label for="editar-usuario-email-{{ $usuario->id }}" class="field-label">Correo electrónico</label>
                        <input id="editar-usuario-email-{{ $usuario->id }}" name="email" type="email" required maxlength="255" value="{{ old('email', $usuario->email) }}" class="field">
                    </div>

                    <div>
                        <label for="editar-usuario-password-{{ $usuario->id }}" class="field-label">Nueva contraseña (opcional)</label>
                        <input id="editar-usuario-password-{{ $usuario->id }}" name="password" type="password" minlength="8" autocomplete="new-password" class="field">
                        <p class="field-help">Déjala vacía para conservar la actual.</p>
                    </div>

                    <div class="form-actions">
                        <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </dialog>

            <!-- Modal: confirmar eliminación -->
            <dialog id="eliminar-usuario-{{ $usuario->id }}" class="dialog-box">
                <div class="dialog-head">
                    <div>
                        <h2 class="dialog-title">Eliminar usuario</h2>
                        <p class="dialog-copy">Esta acción no se puede deshacer.</p>
                    </div>
                    <button type="button" command="close" commandfor="eliminar-usuario-{{ $usuario->id }}" class="z-icon-button" aria-label="Cerrar">
                        <x-z.icon name="x" class="size-4" />
                    </button>
                </div>

                <p class="dialog-copy">¿Seguro que quieres eliminar a «{{ $usuario->name }}»? Se borrarán sus lecturas y suscripciones.</p>

                <div class="form-actions mt-6">
                    <button type="button" command="close" commandfor="eliminar-usuario-{{ $usuario->id }}" class="btn btn-secondary">Cancelar</button>
                    <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" command="close" commandfor="eliminar-usuario-{{ $usuario->id }}" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </dialog>
        @endif
    @endforeach

    @include('partials.modal-scripts')
</x-layouts::app>

@php
    $modal = $rol === \App\Models\User::ROL_ADMIN ? 'crear-admin' : 'crear-cliente';
@endphp

<!-- Modal: crear {{ $rol === \App\Models\User::ROL_ADMIN ? 'administrador' : 'cliente' }} -->
<dialog id="{{ $modal }}" @if ($errors->any() && old('_modal') === $modal) open @endif class="dialog-box">
    <div class="dialog-head">
        <div>
            <h2 class="dialog-title">Nuevo {{ $rol === \App\Models\User::ROL_ADMIN ? 'administrador' : 'cliente' }}</h2>
            <p class="dialog-copy">Crea una cuenta con acceso a la biblioteca.</p>
        </div>
        <button type="button" command="close" commandfor="{{ $modal }}" class="z-icon-button" aria-label="Cerrar">
            <x-z.icon name="x" class="size-4" />
        </button>
    </div>

    @include('usuarios.partials.errores', ['modal' => $modal])

    <form action="{{ route('usuarios.store') }}" method="POST" class="field-grid">
        @csrf
        <input type="hidden" name="_modal" value="{{ $modal }}">
        <input type="hidden" name="rol" value="{{ $rol }}">

        <div>
            <label for="{{ $modal }}-nombre" class="field-label">Nombre</label>
            <input id="{{ $modal }}-nombre" name="name" type="text" required maxlength="255" value="{{ old('name') }}" class="field" placeholder="Ej. Ana García">
        </div>

        <div>
            <label for="{{ $modal }}-email" class="field-label">Correo electrónico</label>
            <input id="{{ $modal }}-email" name="email" type="email" required maxlength="255" value="{{ old('email') }}" class="field" placeholder="Ej. ana@correo.com">
        </div>

        <div>
            <label for="{{ $modal }}-password" class="field-label">Contraseña</label>
            <input id="{{ $modal }}-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="field">
            <p class="field-help">Mínimo 8 caracteres.</p>
        </div>

        <div class="form-actions">
            <button type="button" command="close" commandfor="{{ $modal }}" class="btn btn-secondary">Cancelar</button>
            <button type="submit" command="close" commandfor="{{ $modal }}" class="btn btn-primary">Guardar usuario</button>
        </div>
    </form>
</dialog>

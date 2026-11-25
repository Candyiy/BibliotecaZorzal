<x-layouts::app :title="__('Cuenta suspendida')">
    <div class="z-page">
        <x-z.page-header
            title="Cuenta suspendida"
            subtitle="Tu acceso a la biblioteca está suspendido temporalmente."
        />

        <div class="z-card max-w-xl">
            <p class="z-card-title">Hola, {{ auth()->user()->name }}</p>
            <p class="z-card-note">
                Un administrador suspendió tu cuenta. Mientras dure la suspensión no podrás entrar al catálogo ni leer los PDF.
            </p>

            @if (auth()->user()->suspendido_hasta)
                <p class="z-hint mt-4">
                    La suspensión finaliza el {{ auth()->user()->suspendido_hasta->format('d/m/Y H:i') }}.
                    Después de esa hora podrás ingresar normalmente.
                </p>
            @else
                <p class="z-hint mt-4">Contacta con la biblioteca para conocer la fecha de reactivación.</p>
            @endif

            <div class="form-actions mt-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>

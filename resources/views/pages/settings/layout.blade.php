<div class="flex flex-col gap-6 md:flex-row md:items-start">
    <nav class="flex w-full gap-2 overflow-x-auto border-b border-line pb-2 md:w-56 md:flex-col md:border-b-0 md:border-e md:pb-0 md:pe-4" aria-label="Ajustes">
        <a href="{{ route('profile.edit') }}" wire:navigate
           class="z-nav-link shrink-0 {{ request()->routeIs('profile.edit') ? 'is-active' : '' }}">
            <x-z.icon name="users" class="size-[18px] shrink-0" />
            Perfil
        </a>
        <a href="{{ route('security.edit') }}" wire:navigate
           class="z-nav-link shrink-0 {{ request()->routeIs('security.edit') ? 'is-active' : '' }}">
            <x-z.icon name="shield" class="size-[18px] shrink-0" />
            Seguridad
        </a>
        <a href="{{ route('appearance.edit') }}" wire:navigate
           class="z-nav-link shrink-0 {{ request()->routeIs('appearance.edit') ? 'is-active' : '' }}">
            <x-z.icon name="book-open" class="size-[18px] shrink-0" />
            Apariencia
        </a>
    </nav>

    <section class="min-w-0 flex-1">
        <h2 class="z-card-title">{{ $heading ?? '' }}</h2>
        <p class="z-card-note">{{ $subheading ?? '' }}</p>

        <div class="z-card mt-5 max-w-xl">
            {{ $slot }}
        </div>
    </section>
</div>

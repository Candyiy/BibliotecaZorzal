@props([
    'compact' => false,
])

<flux:dropdown position="bottom" align="end">
    <button
        type="button"
        class="flex items-center gap-2 rounded-lg border border-transparent px-1.5 py-1 text-start transition-colors hover:border-line hover:bg-surface"
        data-test="sidebar-menu-button"
        aria-label="Menú de usuario"
    >
        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand text-xs font-semibold text-white">
            {{ auth()->user()->initials() }}
        </span>

        @unless ($compact)
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</span>
                <span class="block truncate text-xs text-ink-soft">{{ auth()->user()->email }}</span>
            </span>
        @endunless

        <x-z.icon name="chevron-down" class="size-3.5 shrink-0 text-ink-soft" />
    </button>

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-sm">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand text-xs font-semibold text-white">
                {{ auth()->user()->initials() }}
            </span>
            <div class="grid min-w-0 flex-1 leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.item :href="route('profile.edit')" wire:navigate>
            <span class="flex items-center gap-2">
                <x-z.icon name="settings" class="size-4" />
                {{ __('Ajustes') }}
            </span>
        </flux:menu.item>

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item as="button" type="submit" class="w-full cursor-pointer" data-test="logout-button">
                <span class="flex items-center gap-2">
                    <x-z.icon name="log-out" class="size-4" />
                    {{ __('Cerrar sesión') }}
                </span>
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>

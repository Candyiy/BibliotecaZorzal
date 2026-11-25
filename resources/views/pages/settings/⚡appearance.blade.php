<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="flex items-center justify-between gap-4 py-1">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-md border border-brass/50 text-brass-ink">
                    <x-z.icon name="book-open" class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-ink">Papel y Tinta</p>
                    <p class="text-xs text-ink-soft">La biblioteca utiliza exclusivamente el tema claro: marfil papel, verde tinta y latón.</p>
                </div>
            </div>
            <x-z.badge variant="accent">Activo</x-z.badge>
        </div>
    </x-pages::settings.layout>
</section>

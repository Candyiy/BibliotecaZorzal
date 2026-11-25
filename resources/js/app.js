// Evita que un doble clic (o un Enter repetido) envíe dos veces el mismo formulario.
(() => {
    if (window.__bibliotecaDobleClickListos) return;
    window.__bibliotecaDobleClickListos = true;

    document.addEventListener(
        'submit',
        (event) => {
            const formulario = event.target;
            if (! (formulario instanceof HTMLFormElement)) return;

            // Livewire controla el estado de carga de sus propios formularios.
            if (formulario.hasAttribute('wire:submit')) return;

            if (formulario.dataset.enviando === 'true') {
                event.preventDefault();

                return;
            }

            formulario.dataset.enviando = 'true';

            const botones = Array.from(
                formulario.querySelectorAll('button[type="submit"], input[type="submit"]'),
            );

            // Incluye botones asociados al formulario desde fuera con el atributo form.
            if (formulario.id) {
                document.querySelectorAll('[type="submit"][form]').forEach((boton) => {
                    if (boton.getAttribute('form') === formulario.id && ! botones.includes(boton)) {
                        botones.push(boton);
                    }
                });
            }

            const enfocado = document.activeElement;
            if (enfocado instanceof HTMLElement && enfocado.form === formulario && ! botones.includes(enfocado)) {
                botones.push(enfocado);
            }

            botones.forEach((boton) => {
                boton.disabled = true;
                boton.setAttribute('aria-busy', 'true');
            });
        },
        true,
    );
})();

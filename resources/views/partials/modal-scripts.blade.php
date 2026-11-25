<script>
    (() => {
        // Livewire vuelve a ejecutar los scripts al navegar con wire:navigate.
        // El guard evita duplicar listeners y redeclarar constantes globales.
        if (window.__bibliotecaModalesListos) return;
        window.__bibliotecaModalesListos = true;

        const soportaCommand = 'command' in HTMLButtonElement.prototype;

        // Los campos de texto bibliográfico no admiten dígitos; el servidor
        // también lo valida por si el navegador se omite.
        document.addEventListener('input', (event) => {
            const campo = event.target;

            if (! (campo instanceof HTMLInputElement || campo instanceof HTMLTextAreaElement)) return;
            if (! campo.hasAttribute('data-sin-numeros')) return;

            const valor = campo.value;
            const limpio = valor.replace(/[0-9]/g, '');

            if (limpio === valor) return;

            const posicion = campo.selectionStart;
            campo.value = limpio;

            if (posicion !== null) {
                const nuevaPosicion = valor.slice(0, posicion).replace(/[0-9]/g, '').length;
                campo.setSelectionRange(nuevaPosicion, nuevaPosicion);
            }
        });

        // Los errores de validación renderizan el diálogo con el atributo open,
        // que no es modal. Lo promovemos para que se vea como diálogo real.
        const abrirDialogosAbiertos = () => {
            document.querySelectorAll('dialog[open]').forEach((dialogo) => {
                try {
                    if (dialogo.matches(':modal')) return;
                } catch (error) {
                    // Navegador sin soporte para :modal.
                }

                dialogo.removeAttribute('open');

                if (typeof dialogo.showModal === 'function') {
                    dialogo.showModal();
                }
            });
        };

        document.addEventListener('click', (event) => {
            if (event.target instanceof HTMLDialogElement && event.target.tagName === 'DIALOG') {
                event.target.close();
                return;
            }

            const invocador = event.target.closest('[command][commandfor]');
            if (!invocador) return;

            const dialogo = document.getElementById(invocador.getAttribute('commandfor'));
            if (!dialogo) return;

            const accion = invocador.getAttribute('command');

            // Respaldo para navegadores que aún no soportan invoker commands.
            if (!soportaCommand) {
                if (invocador.type !== 'submit') event.preventDefault();

                if (accion === 'show-modal') {
                    dialogo.showModal ? dialogo.showModal() : dialogo.setAttribute('open', '');
                } else if (accion === 'close') {
                    dialogo.close ? dialogo.close() : dialogo.removeAttribute('open');
                }
            }

            // Carga el PDF solo cuando se abre su lector.
            if (accion === 'show-modal' && invocador.dataset.pdfUrl) {
                const marco = dialogo.querySelector('iframe');
                if (marco) marco.src = invocador.dataset.pdfUrl;
            }
        });

        // El evento close no burbujea, se escucha en fase de captura.
        document.addEventListener('close', (event) => {
            const marco = event.target.querySelector?.('iframe');
            if (marco) marco.removeAttribute('src');
        }, true);

        document.addEventListener('DOMContentLoaded', abrirDialogosAbiertos);
        document.addEventListener('livewire:navigated', abrirDialogosAbiertos);
        abrirDialogosAbiertos();
    })();
</script>

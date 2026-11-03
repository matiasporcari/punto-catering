/**
 * Comportamientos comunes a todo el sitio.
 * Se activan mediante atributos en el HTML, para no repetir código en cada página.
 */

'use strict';

// Indica a la hoja de estilos que JavaScript está disponible.
document.documentElement.classList.add('con-js');

/** Abre y cierra el menú de navegación en pantallas pequeñas. */
function inicializarMenuMovil() {
    const boton = document.querySelector('.boton-menu');
    const navegacion = document.querySelector('.navegacion');

    if (!boton || !navegacion) {
        return;
    }

    boton.addEventListener('click', () => {
        const estaAbierto = navegacion.classList.toggle('abierta');
        boton.setAttribute('aria-expanded', String(estaAbierto));
        boton.setAttribute('aria-label', estaAbierto ? 'Cerrar menú' : 'Abrir menú');
    });
}

/** Pide confirmación antes de enviar los formularios con el atributo data-confirmar. */
function inicializarConfirmaciones() {
    document.addEventListener('submit', (evento) => {
        const mensaje = evento.target.dataset.confirmar;

        if (mensaje && !window.confirm(mensaje)) {
            evento.preventDefault();
        }
    });
}

/** Envía el formulario al cambiar una lista con el atributo data-enviar-al-cambiar. */
function inicializarEnvioAlCambiar() {
    document.querySelectorAll('[data-enviar-al-cambiar]').forEach((lista) => {
        lista.addEventListener('change', () => lista.form.submit());
    });
}

/** Abre el diálogo de impresión desde los botones con el atributo data-imprimir. */
function inicializarImpresion() {
    document.querySelectorAll('[data-imprimir]').forEach((boton) => {
        boton.addEventListener('click', () => window.print());
    });
}

inicializarMenuMovil();
inicializarConfirmaciones();
inicializarEnvioAlCambiar();
inicializarImpresion();

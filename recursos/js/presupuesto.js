/**
 * Formulario de presupuesto: calcula el precio estimado en vivo.
 * Es solo una ayuda para el usuario: el servidor vuelve a validar y calcular todo al guardar.
 */

'use strict';

const formularioPresupuesto = document.getElementById('formulario-presupuesto');
const campoPersonas = document.getElementById('cantidad_personas');
const casillasMenus = formularioPresupuesto.querySelectorAll('input[name="menus[]"]');
const listaResumen = document.getElementById('resumen-lista');
const textoPersonas = document.getElementById('resumen-personas');
const textoTotal = document.getElementById('resumen-total');
const avisoMinimo = document.getElementById('aviso-minimo');

const formateadorMoneda = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 0 });

/** Formatea un importe igual que el servidor: "$ 1.234.567". */
function formatearMoneda(importe) {
    return `$ ${formateadorMoneda.format(importe)}`;
}

/** Vuelve a dibujar la lista de menús elegidos en el resumen. */
function dibujarResumen(menusElegidos, personas) {
    listaResumen.replaceChildren();

    if (menusElegidos.length === 0) {
        const vacio = document.createElement('li');
        vacio.className = 'texto-suave';
        vacio.textContent = 'Todavía no elegiste menús.';
        listaResumen.append(vacio);
        return;
    }

    menusElegidos.forEach((menu) => {
        const fila = document.createElement('li');
        const nombre = document.createElement('span');
        const subtotal = document.createElement('span');

        nombre.textContent = menu.nombre;
        subtotal.textContent = formatearMoneda(menu.precio * personas);

        fila.append(nombre, subtotal);
        listaResumen.append(fila);
    });
}

/** Avisa si algún menú elegido pide más personas que las indicadas. */
function actualizarAvisoMinimo(menusElegidos, personas) {
    const menuNoCumple = personas > 0 && menusElegidos.find((menu) => personas < menu.minimo);

    avisoMinimo.classList.toggle('oculto', !menuNoCumple);

    if (menuNoCumple) {
        avisoMinimo.textContent = `"${menuNoCumple.nombre}" requiere al menos ${menuNoCumple.minimo} personas.`;
    }
}

/** Recalcula el resumen completo con los valores actuales del formulario. */
function actualizarPresupuesto() {
    const personas = Math.max(0, parseInt(campoPersonas.value, 10) || 0);

    const menusElegidos = Array.from(casillasMenus)
        .filter((casilla) => casilla.checked)
        .map((casilla) => ({
            nombre: casilla.dataset.nombre,
            precio: parseFloat(casilla.dataset.precio),
            minimo: parseInt(casilla.dataset.minimo, 10),
        }));

    const total = menusElegidos.reduce((suma, menu) => suma + menu.precio * personas, 0);

    dibujarResumen(menusElegidos, personas);
    actualizarAvisoMinimo(menusElegidos, personas);
    textoPersonas.textContent = personas;
    textoTotal.textContent = formatearMoneda(total);
}

formularioPresupuesto.addEventListener('input', actualizarPresupuesto);

// Primer cálculo, por si el formulario llega con datos: edición o menú elegido en el catálogo.
actualizarPresupuesto();

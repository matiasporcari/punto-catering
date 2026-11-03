/**
 * Filtros del catálogo: actualiza los resultados sin recargar la página.
 */

'use strict';

const formularioFiltros = document.getElementById('filtros-catalogo');
const contenedorResultados = document.getElementById('resultados-catalogo');
const contadorResultados = document.getElementById('contador-resultados');

// Tiempo de espera tras cada tecla, para no consultar al servidor por cada letra.
const ESPERA_ESCRITURA_MS = 250;

let temporizadorEscritura = null;
let peticionEnCurso = null;

/** Arma los parámetros de búsqueda con los filtros que tienen un valor. */
function obtenerParametrosFiltros() {
    const parametros = new URLSearchParams();

    new FormData(formularioFiltros).forEach((valor, campo) => {
        if (valor !== '') {
            parametros.append(campo, valor);
        }
    });

    return parametros;
}

/** Consulta al servidor y reemplaza los resultados del catálogo. */
async function actualizarCatalogo() {
    // Si había una búsqueda anterior sin terminar, se cancela: su respuesta ya no sirve.
    if (peticionEnCurso) {
        peticionEnCurso.abort();
    }
    peticionEnCurso = new AbortController();

    const parametros = obtenerParametrosFiltros();
    const consulta = parametros.toString();

    try {
        const respuesta = await fetch(`${formularioFiltros.dataset.api}?${consulta}`, {
            signal: peticionEnCurso.signal,
        });

        if (!respuesta.ok) {
            throw new Error(`Respuesta inesperada del servidor: ${respuesta.status}`);
        }

        const datos = await respuesta.json();

        contenedorResultados.innerHTML = datos.html;
        contadorResultados.textContent = `${datos.cantidad} ${datos.cantidad === 1 ? 'menú' : 'menús'}`;

        // La dirección refleja los filtros, para poder compartirla o recargarla.
        history.replaceState(null, '', consulta ? `?${consulta}` : window.location.pathname);
    } catch (error) {
        if (error.name !== 'AbortError') {
            contadorResultados.textContent = 'No se pudo actualizar el catálogo. Probá de nuevo.';
        }
    }
}

formularioFiltros.addEventListener('input', (evento) => {
    clearTimeout(temporizadorEscritura);

    // Solo se espera al escribir; las listas y casillas responden en el momento.
    const espera = evento.target.type === 'search' ? ESPERA_ESCRITURA_MS : 0;
    temporizadorEscritura = setTimeout(actualizarCatalogo, espera);
});

formularioFiltros.addEventListener('submit', (evento) => {
    evento.preventDefault();
    actualizarCatalogo();
});

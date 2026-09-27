/**
 * PDF.js pesa ~460 KB, asi que se carga en diferido: el chunk de la vista de
 * cobro se queda pequeno y la libreria solo baja cuando alguien abre la
 * previsualizacion de una factura.
 */
let pdfjsPromise = null;

async function getPdfjs() {
    if (!pdfjsPromise) {
        pdfjsPromise = Promise.all([
            import('pdfjs-dist'),
            import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
        ]).then(([pdfjsLib, worker]) => {
            // El parseo ocurre en un worker aparte; Vite resuelve la URL con ?url.
            pdfjsLib.GlobalWorkerOptions.workerSrc = worker.default;
            return pdfjsLib;
        });
    }

    return pdfjsPromise;
}

/**
 * Descarga un PDF con el token de Sanctum y lo devuelve como blob.
 * El endpoint exige Authorization, asi que un <a href> plano devolveria 401.
 */
export async function fetchPdf(url, token) {
    const response = await fetch(url, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
    });

    if (!response.ok) {
        throw new Error(`No se pudo cargar el PDF (${response.status})`);
    }

    return response.blob();
}

export async function openPdf(blob) {
    const pdfjsLib = await getPdfjs();
    return pdfjsLib.getDocument({ data: await blob.arrayBuffer() }).promise;
}

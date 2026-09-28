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

/**
 * Abre un PDF y devuelve el loading task, no el PDFDocumentProxy.
 *
 * Es a proposito: en pdf.js v6 el proxy solo expone cleanup(), mientras que
 * destroy() —el unico que cierra el documento y libera el worker— vive en el
 * PDFDocumentLoadingTask que devuelve getDocument(). Si solo se devolviera el
 * proxy, despues no habria forma de cerrar el documento y cada apertura
 * dejaria un worker huerfano.
 *
 * El proxy se obtiene con `await task.promise`.
 */
export async function openPdf(blob) {
    const pdfjsLib = await getPdfjs();
    return pdfjsLib.getDocument({ data: await blob.arrayBuffer() });
}

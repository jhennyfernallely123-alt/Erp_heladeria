<template>
    <AppModal :open="open" :title="title" :subtitle="subtitle" size="xl" @close="emit('close')">
        <!-- Barra de herramientas -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-aguamarina-100"
        >
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="p-2 rounded-lg text-petrol-600 hover:bg-aguamarina-50 transition disabled:opacity-40"
                    :disabled="page <= 1"
                    aria-label="Página anterior"
                    @click="goTo(page - 1)"
                >
                    <AppIcon :name="ChevronLeft" :size="17" />
                </button>

                <span class="text-xs text-niebla-400 px-1.5 tabular-nums">
                    Página {{ page }} de {{ totalPages || '—' }}
                </span>

                <button
                    type="button"
                    class="p-2 rounded-lg text-petrol-600 hover:bg-aguamarina-50 transition disabled:opacity-40"
                    :disabled="page >= totalPages"
                    aria-label="Página siguiente"
                    @click="goTo(page + 1)"
                >
                    <AppIcon :name="ChevronRight" :size="17" />
                </button>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="p-2 rounded-lg text-petrol-600 hover:bg-aguamarina-50 transition"
                    aria-label="Alejar"
                    @click="changeZoom(-0.2)"
                >
                    <AppIcon :name="ZoomOut" :size="17" />
                </button>

                <span class="text-xs text-niebla-400 w-12 text-center tabular-nums">
                    {{ Math.round(zoom * 100) }}%
                </span>

                <button
                    type="button"
                    class="p-2 rounded-lg text-petrol-600 hover:bg-aguamarina-50 transition"
                    aria-label="Acercar"
                    @click="changeZoom(0.2)"
                >
                    <AppIcon :name="ZoomIn" :size="17" />
                </button>

                <button
                    type="button"
                    class="ml-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-petrol-600 hover:bg-aguamarina-50 transition"
                    @click="resetZoom"
                >
                    Ajustar
                </button>
            </div>
        </div>

        <!-- Estados -->
        <div v-if="loading" class="py-24 flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-aguamarina-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <p class="text-sm text-niebla-400">Generando el ticket…</p>
        </div>

        <div v-else-if="error" class="py-16 flex flex-col items-center gap-3 text-center">
            <div class="h-14 w-14 rounded-2xl bg-rose-50 flex items-center justify-center">
                <AppIcon :name="AlertCircle" :size="26" class="text-rose-500" />
            </div>
            <p class="text-sm font-semibold text-petrol-800">No se pudo previsualizar el ticket</p>
            <p class="text-xs text-niebla-400 max-w-sm">{{ error }}</p>
        </div>

        <!-- Documento -->
        <div v-else class="flex justify-center">
            <canvas
                ref="canvas"
                class="max-w-full rounded-lg shadow-suave border border-aguamarina-100 bg-white"
            />
        </div>

        <template #footer>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p v-if="meta" class="text-[11px] text-niebla-300">
                    {{ meta.invoice_number }} · Ticket 80 mm · {{ formattedSize }}
                    <span v-if="meta.generated_at"> · generado {{ formattedDate }}</span>
                </p>
                <span v-else></span>

                <div class="flex items-center gap-2">
                    <AppButton
                        v-if="url"
                        variant="secondary"
                        size="sm"
                        label="Descargar"
                        :icon="Download"
                        @click="download"
                    />
                    <AppButton size="sm" label="Imprimir" :icon="Printer" @click="printDocument" />
                </div>
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { AlertCircle, ChevronLeft, ChevronRight, Download, Printer, ZoomIn, ZoomOut } from 'lucide-vue-next';
import AppButton from '../ui/AppButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import AppModal from '../ui/AppModal.vue';
import { fetchPdf, openPdf } from '../../lib/pdf';
import { useAuthStore } from '../../stores/auth';
import api from '../../api';

const props = defineProps({
    open: { type: Boolean, default: false },
    invoiceId: { type: [Number, String], default: null },
    title: { type: String, default: 'Factura' },
    subtitle: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const authStore = useAuthStore();
const canvas = ref(null);

const loading = ref(false);
const error = ref('');
const meta = ref(null);

/** URL del documento que se muestra: el ticket termico de 80 mm. */
const url = ref('');

/** Bytes reales del blob cargado, que es el peso del ticket y no el de la A4. */
const docSize = ref(0);

const page = ref(1);
const totalPages = ref(0);
const zoom = ref(1);
const fitScale = ref(1);

/** Proxy del documento, para leer paginas y dibujar. */
let doc = null;

/**
 * Loading task de pdf.js. Es el unico objeto con destroy() en v6, asi que es
 * lo que hay que cerrar para no dejar workers huerfanos.
 */
let loadingTask = null;
let renderTask = null;

/**
 * Token de la carga en curso. Si el modal se cierra mientras el PDF viaja, la
 * respuesta llega tarde y sin este control se guardaria un documento que ya
 * nadie limpio, quedado huerfano para siempre.
 */
let loadToken = 0;

const formattedSize = computed(() => {
    const bytes = docSize.value;
    if (!bytes) return '';
    return bytes > 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.round(bytes / 1024)} KB`;
});

const formattedDate = computed(() => {
    if (!meta.value?.generated_at) return '';
    return new Date(meta.value.generated_at).toLocaleString('es-CO');
});

const cleanup = () => {
    // Invalida cualquier carga en vuelo para que no se guarde nada tarde.
    loadToken += 1;

    if (renderTask) {
        renderTask.cancel();
        renderTask = null;
    }

    if (loadingTask) {
        // En pdf.js v6 destroy() pertenece al loading task, no al proxy:
        // llamarlo sobre el proxy daba "doc.destroy is not a function".
        loadingTask.destroy().catch(() => {});
        loadingTask = null;
    }

    doc = null;
};

/**
 * Espera a que el <canvas> este montado.
 *
 * Mientras `loading` es true el modal muestra el spinner en lugar del
 * documento, asi que el canvas recien existe cuando esa bandera baja. Sin
 * esta espera, renderPage() se encuentra canvas.value === null y sale sin
 * dibujar, dejando el canvas en su tamano por defecto de 300x150.
 */
const waitForCanvas = async (attempts = 40) => {
    for (let i = 0; i < attempts; i++) {
        if (canvas.value) return true;
        await nextTick();
        await new Promise((resolve) => setTimeout(resolve, 25));
    }
    return false;
};

const renderPage = async () => {
    if (!doc || !canvas.value) return;

    // En pdf.js v6 `getPage()` ya devuelve una Promise<PDFPageProxy>. El codigo
    // anterior hacia `await doc.getPage(n).promise`, que en v6 es
    // `await undefined` y reventaba con "Cannot read properties of undefined
    // (reading 'getViewport')", dejando el canvas en blanco.
    const pdfPage = await doc.getPage(page.value);
    const base = pdfPage.getViewport({ scale: 1 });

    // El ancho disponible se mide sobre el contenedor, no sobre la ventana,
    // porque el modal cambia de tamano entre movil y escritorio.
    const available = canvas.value.parentElement.clientWidth - 2;

    // Un ticket de 80mm es angosto y muy alto. Si solo se ajustara al ancho,
    // "Ajustar" dejaria un documento mas largo que la pantalla y habria que
    // scrollear siempre, asi que tambien se limita por alto.
    const availableHeight = window.innerHeight - 220;

    fitScale.value = Math.max(
        0.35,
        Math.min(1.6, available / base.width, availableHeight / base.height)
    );

    const viewport = pdfPage.getViewport({ scale: fitScale.value * zoom.value });
    const context = canvas.value.getContext('2d');
    const ratio = window.devicePixelRatio || 1;

    canvas.value.width = viewport.width * ratio;
    canvas.value.height = viewport.height * ratio;
    canvas.value.style.width = `${viewport.width}px`;
    canvas.value.style.height = `${viewport.height}px`;

    context.setTransform(ratio, 0, 0, ratio, 0, 0);

    renderTask = pdfPage.render({ canvasContext: context, viewport });
    await renderTask.promise;
    renderTask = null;
};

const load = async () => {
    if (!props.invoiceId) return;

    const token = ++loadToken;
    loading.value = true;
    error.value = '';
    page.value = 1;
    zoom.value = 1;

    try {
        const res = await api.get(`/invoices/${props.invoiceId}/preview`);
        if (token !== loadToken) return;

        meta.value = res.data.data;

        // Lo que se previsualiza, imprime y descarga es el ticket de 80 mm,
        // que es el documento que sale por la impresora termica del salon.
        url.value = res.data.data.ticket_url;

        const blob = await fetchPdf(url.value, authStore.token);
        if (token !== loadToken) return;

        docSize.value = blob.size;
        loadingTask = await openPdf(blob);

        if (token !== loadToken) {
            // El modal se cerro mientras se abria el PDF: hay que cerrar esto
            // a mano porque cleanup() ya corrio y no lo vio.
            loadingTask.destroy().catch(() => {});
            loadingTask = null;
            return;
        }

        doc = await loadingTask.promise;
        totalPages.value = doc.numPages;
    } catch (err) {
        if (token !== loadToken) return;
        error.value = err.response?.data?.message || err.message || 'Error desconocido';
    } finally {
        if (token === loadToken) loading.value = false;
    }

    if (error.value || token !== loadToken) return;

    // El dibujo va despues del finally, no antes: recien cuando `loading` es
    // false el template monta el <canvas> en lugar del spinner. Ademas asi un
    // error de red no intenta dibujar sobre un canvas que no existe.
    if (await waitForCanvas()) {
        try {
            await renderPage();
        } catch (err) {
            error.value = err.message || 'No se pudo dibujar el ticket';
        }
    }
};

const goTo = async (next) => {
    if (next < 1 || next > totalPages.value) return;
    page.value = next;
    await renderPage();
};

const changeZoom = async (delta) => {
    zoom.value = Math.min(3, Math.max(0.4, zoom.value + delta));
    await renderPage();
};

const resetZoom = async () => {
    zoom.value = 1;
    await renderPage();
};

const download = async () => {
    if (!url.value) return;
    const blob = await fetchPdf(url.value, authStore.token);
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${meta.value?.invoice_number || 'ticket'}-ticket.pdf`;
    link.click();
    URL.revokeObjectURL(link.href);
};

/**
 * Imprimir delegando en el visor de PDF del navegador: mantiene el layout
 * exacto y usa la impresora del sistema sin re-renderizar nada.
 */
const printDocument = async () => {
    if (!url.value) return;

    try {
        const blob = await fetchPdf(url.value, authStore.token);
        const printUrl = URL.createObjectURL(blob);
        const frame = document.createElement('iframe');
        frame.style.position = 'fixed';
        frame.style.right = '0';
        frame.style.bottom = '0';
        frame.style.width = '0';
        frame.style.height = '0';
        frame.style.border = '0';
        frame.src = printUrl;
        document.body.appendChild(frame);

        await new Promise((resolve) => {
            frame.onload = resolve;
        });

        frame.contentWindow.focus();
        frame.contentWindow.print();
    } catch (err) {
        error.value = err.message || 'No se pudo imprimir';
    }
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            load();
        } else {
            cleanup();
        }
    }
);
</script>

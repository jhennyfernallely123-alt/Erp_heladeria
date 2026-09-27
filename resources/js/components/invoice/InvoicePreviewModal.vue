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
            <p class="text-sm text-niebla-400">Generando la factura…</p>
        </div>

        <div v-else-if="error" class="py-16 flex flex-col items-center gap-3 text-center">
            <div class="h-14 w-14 rounded-2xl bg-rose-50 flex items-center justify-center">
                <AppIcon :name="AlertCircle" :size="26" class="text-rose-500" />
            </div>
            <p class="text-sm font-semibold text-petrol-800">No se pudo previsualizar la factura</p>
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
                    {{ meta.invoice_number }} · {{ formattedSize }}
                    <span v-if="meta.size_bytes"> · generado {{ formattedDate }}</span>
                </p>
                <span v-else></span>

                <div class="flex items-center gap-2">
                    <AppButton
                        v-if="showTicket"
                        variant="secondary"
                        size="sm"
                        label="Ticket 80mm"
                        :icon="Receipt"
                        @click="printDocument(ticketUrl)"
                    />
                    <AppButton
                        v-if="ticketUrl"
                        variant="secondary"
                        size="sm"
                        label="Descargar"
                        :icon="Download"
                        @click="download"
                    />
                    <AppButton size="sm" label="Imprimir" :icon="Printer" @click="printDocument(url)" />
                </div>
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { AlertCircle, ChevronLeft, ChevronRight, Download, Printer, Receipt, ZoomIn, ZoomOut } from 'lucide-vue-next';
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
    showTicket: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const authStore = useAuthStore();
const canvas = ref(null);

const loading = ref(false);
const error = ref('');
const meta = ref(null);
const url = ref('');
const ticketUrl = ref('');

const page = ref(1);
const totalPages = ref(0);
const zoom = ref(1);
const fitScale = ref(1);

let doc = null;
let objectUrl = '';
let renderTask = null;

const formattedSize = computed(() => {
    const bytes = meta.value?.size_bytes;
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
    if (renderTask) {
        renderTask.cancel();
        renderTask = null;
    }
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = '';
    }
    if (doc) {
        doc.destroy();
        doc = null;
    }
};

const renderPage = async () => {
    if (!doc || !canvas.value) return;

    const target = doc.numPages === 1 ? doc.getPage(1) : doc.getPage(page.value);
    const pdfPage = await target.promise;
    const base = pdfPage.getViewport({ scale: 1 });

    // El ancho disponible se mide sobre el contenedor, no sobre la ventana,
    // porque el modal cambia de tamano entre movil y escritorio.
    const available = canvas.value.parentElement.clientWidth - 2;
    fitScale.value = Math.min(1.6, available / base.width);

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

    loading.value = true;
    error.value = '';
    page.value = 1;
    zoom.value = 1;

    try {
        const res = await api.get(`/invoices/${props.invoiceId}/preview`);
        meta.value = res.data.data;
        url.value = res.data.data.url;
        ticketUrl.value = res.data.data.ticket_url;

        const blob = await fetchPdf(url.value, authStore.token);
        doc = await openPdf(blob);
        totalPages.value = doc.numPages;

        objectUrl = URL.createObjectURL(blob);

        await nextTick();
        await renderPage();
    } catch (err) {
        error.value = err.response?.data?.message || err.message || 'Error desconocido';
    } finally {
        loading.value = false;
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
    link.download = `${meta.value?.invoice_number || 'factura'}.pdf`;
    link.click();
    URL.revokeObjectURL(link.href);
};

/**
 * Imprimir delegando en el visor de PDF del navegador: mantiene el layout
 * exacto y usa la impresora del sistema sin re-renderizar nada.
 */
const printDocument = async (target) => {
    if (!target) return;

    try {
        const blob = await fetchPdf(target, authStore.token);
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

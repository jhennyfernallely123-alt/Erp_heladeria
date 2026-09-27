<template>
    <AppModal
        :open="open"
        :title="isEditing ? 'Editar producto' : 'Nuevo producto'"
        subtitle="Los datos de stock se gestionan en el módulo de Inventario"
        size="lg"
        @close="emit('close')"
    >
        <div class="flex gap-1 p-1 bg-slate-100 rounded-xl mb-5">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition"
                :class="
                    activeTab === tab.id
                        ? 'bg-white text-slate-900 shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'
                "
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </button>
        </div>

        <div v-if="activeTab === 'data'" class="space-y-4">
            <div>
                <p class="text-xs font-semibold text-slate-700 mb-1.5">Imagen del producto</p>
                <div class="flex items-center gap-4">
                    <div
                        class="h-20 w-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0"
                    >
                        <img
                            v-if="imagePreview"
                            :src="imagePreview"
                            alt="Vista previa"
                            class="h-full w-full object-cover"
                        />
                        <AppIcon v-else-if="form.image" :name="Package" :size="26" class="text-slate-400" />
                        <AppIcon v-else :name="ImagePlus" :size="26" class="text-slate-400" />
                    </div>
                    <div class="space-y-2">
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            @change="onFileChange"
                        />
                        <AppButton
                            variant="secondary"
                            size="sm"
                            label="Seleccionar imagen"
                            :icon="Upload"
                            @click="fileInput.click()"
                        />
                        <p class="text-[11px] text-slate-500">JPG, PNG o WEBP. Máximo 2MB.</p>
                    </div>
                </div>
                <p v-if="errors.image" class="text-xs text-rose-600 mt-1.5">{{ errors.image[0] }}</p>
            </div>

            <AppInput
                v-model="form.name"
                label="Nombre"
                placeholder="Vaso de helado"
                :required="true"
                :error="firstError('name')"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <AppSelect
                    v-model="form.category_id"
                    label="Categoría"
                    :required="true"
                    :error="firstError('category_id')"
                >
                    <option :value="null" disabled>Selecciona una categoría</option>
                    <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </AppSelect>

                <AppInput
                    v-model="form.sale_price"
                    label="Precio de venta"
                    type="number"
                    :required="true"
                    :error="firstError('sale_price')"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <AppInput
                    v-model="form.cost_price"
                    label="Precio de costo"
                    type="number"
                    :error="firstError('cost_price')"
                />
                <div class="space-y-1.5">
                    <p class="text-xs font-semibold text-slate-700">Margen estimado</p>
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold"
                        :class="marginClass"
                    >
                        {{ marginLabel }}
                    </div>
                </div>
            </div>

            <AppTextarea
                v-model="form.description"
                label="Descripción"
                :rows="3"
                :error="firstError('description')"
            />

            <label class="flex items-center gap-2.5 cursor-pointer">
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="text-sm text-slate-700">Producto activo</span>
            </label>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="(variant, index) in form.variants"
                :key="index"
                class="rounded-xl border border-slate-200 p-4 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-700">Variante {{ index + 1 }}</p>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                        :aria-label="`Quitar variante ${index + 1}`"
                        @click="removeVariant(index)"
                    >
                        <AppIcon :name="Trash2" :size="15" />
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <AppInput v-model="variant.name" label="Nombre" placeholder="3 Bolas" />
                    <AppInput v-model="variant.cost_price" label="Costo" type="number" />
                    <AppInput v-model="variant.sale_price" label="Venta" type="number" />
                </div>
            </div>

            <AppButton
                variant="secondary"
                size="sm"
                label="Agregar variante"
                :icon="Plus"
                @click="addVariant"
            />
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="emit('close')" />
                <AppButton
                    :label="isEditing ? 'Guardar cambios' : 'Crear producto'"
                    :loading="productStore.saving"
                    @click="save"
                />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { ImagePlus, Package, Plus, Trash2, Upload } from 'lucide-vue-next';
import AppModal from './ui/AppModal.vue';
import AppButton from './ui/AppButton.vue';
import AppInput from './ui/AppInput.vue';
import AppSelect from './ui/AppSelect.vue';
import AppTextarea from './ui/AppTextarea.vue';
import AppIcon from './ui/AppIcon.vue';
import { useProductStore } from '../stores/products';
import { useToastStore } from '../stores/toast';

const props = defineProps({
    open: { type: Boolean, default: false },
    product: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const productStore = useProductStore();
const toastStore = useToastStore();

const fileInput = ref(null);
const activeTab = ref('data');
const errors = ref({});
const pendingFile = ref(null);
const imagePreview = ref('');

const tabs = [
    { id: 'data', label: 'Datos' },
    { id: 'variants', label: 'Variantes' },
];

const isEditing = computed(() => props.product !== null);

const emptyForm = () => ({
    name: '',
    category_id: null,
    description: '',
    image: '',
    cost_price: 0,
    sale_price: 0,
    is_active: true,
    variants: [],
});

const form = reactive(emptyForm());

const firstError = (field) => (errors.value[field]?.[0] || '');

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        errors.value = {};
        activeTab.value = 'data';
        pendingFile.value = null;
        imagePreview.value = props.product?.image ? `/storage/${props.product.image}` : '';

        Object.assign(form, emptyForm());

        if (props.product) {
            form.name = props.product.name;
            form.category_id = props.product.category_id;
            form.description = props.product.description || '';
            form.image = props.product.image || '';
            form.cost_price = props.product.cost_price;
            form.sale_price = props.product.sale_price;
            form.is_active = props.product.is_active;
            form.variants = (props.product.variants || []).map((variant) => ({
                name: variant.name,
                cost_price: variant.cost_price,
                sale_price: variant.sale_price,
            }));
        }
    }
);

const margin = computed(() => Number(form.sale_price || 0) - Number(form.cost_price || 0));
const marginClass = computed(() => (margin.value > 0 ? 'text-emerald-700' : 'text-slate-500'));

const marginLabel = computed(() => {
    const sale = Number(form.sale_price || 0);

    if (sale <= 0) {
        return '—';
    }

    const percent = Math.round((margin.value / sale) * 100);
    return `$ ${margin.value.toLocaleString('es-CO')} (${percent}%)`;
});

const onFileChange = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        errors.value = { ...errors.value, image: ['La imagen no puede superar los 2MB.'] };
        event.target.value = '';
        return;
    }

    pendingFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
    delete errors.value.image;
};

const addVariant = () => {
    form.variants.push({ name: '', cost_price: 0, sale_price: 0 });
};

const removeVariant = (index) => {
    form.variants.splice(index, 1);
};

const buildPayload = () => ({
    name: form.name,
    category_id: form.category_id,
    description: form.description || null,
    cost_price: Number(form.cost_price || 0),
    sale_price: Number(form.sale_price || 0),
    is_active: form.is_active,
    has_variants: form.variants.length > 0,
    variants: form.variants
        .filter((variant) => variant.name)
        .map((variant) => ({
            name: variant.name,
            cost_price: Number(variant.cost_price || 0),
            sale_price: Number(variant.sale_price || 0),
        })),
});

const save = async () => {
    errors.value = {};

    try {
        if (isEditing.value) {
            await productStore.updateProduct(props.product.id, buildPayload());

            if (pendingFile.value) {
                await productStore.uploadImage(props.product.id, pendingFile.value);
            }

            toastStore.success('Producto actualizado');
        } else {
            const created = await productStore.createProduct(buildPayload());

            if (pendingFile.value) {
                await productStore.uploadImage(created.id, pendingFile.value);
            }

            toastStore.success('Producto creado');
        }

        emit('saved');
        emit('close');
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
            activeTab.value = 'data';
            toastStore.error('Revisa los campos marcados');
        } else {
            toastStore.error(err.response?.data?.message || 'No se pudo guardar el producto');
        }
    }
};
</script>

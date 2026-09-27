<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <PageHeader title="Productos" subtitle="Catálogo de helado, bebidas y postres">
            <template #actions>
                <AppButton label="Nuevo producto" :icon="Plus" @click="openCreate" />
            </template>
        </PageHeader>

        <div
            class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3"
        >
            <div class="flex-1 min-w-[220px]">
                <AppInput v-model="search" placeholder="Buscar por nombre o descripción" :icon="Search" />
            </div>
            <div class="min-w-[180px]">
                <AppSelect v-model="categoryId">
                    <option :value="null">Todas las categorías</option>
                    <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </AppSelect>
            </div>
            <div class="min-w-[150px]">
                <AppSelect v-model="statusFilter">
                    <option value="all">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </AppSelect>
            </div>
            <span class="text-xs text-slate-500">{{ filtered.length }} productos</span>
        </div>

        <div class="space-y-4">
            <AppTable
                :loading="productStore.loading"
                :total="filtered.length"
                empty-title="Sin productos"
                empty-description="Ajusta la búsqueda o crea el primer producto del catálogo."
            >
                <template #head>
                    <th class="p-3.5">Producto</th>
                    <th class="p-3.5">Categoría</th>
                    <th class="p-3.5 text-right">Precio</th>
                    <th class="p-3.5 text-center">Estado</th>
                    <th class="p-3.5 text-right">Acciones</th>
                </template>

                <tr
                    v-for="product in paged"
                    :key="product.id"
                    class="hover:bg-slate-50 transition-colors"
                >
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0"
                            >
                                <img
                                    v-if="product.image"
                                    :src="imageUrl(product.image)"
                                    :alt="product.name"
                                    class="h-full w-full object-cover"
                                />
                                <AppIcon
                                    v-else
                                    :name="categoryIcon(product)"
                                    :size="20"
                                    class="text-slate-400"
                                />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">
                                    {{ product.name }}
                                </p>
                                <p
                                    v-if="product.description"
                                    class="text-xs text-slate-400 truncate"
                                >
                                    {{ product.description }}
                                </p>
                                <AppBadge
                                    v-if="product.variants?.length"
                                    class="mt-1"
                                    :label="`${product.variants.length} variantes`"
                                />
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5">
                        <AppBadge :label="product.category?.name || 'Sin categoría'" />
                    </td>
                    <td class="p-3.5 text-right font-semibold text-slate-800">
                        $ {{ formatMoney(product.sale_price) }}
                    </td>
                    <td class="p-3.5 text-center">
                        <AppBadge
                            :tone="product.is_active ? 'success' : 'warning'"
                            dot
                            :label="product.is_active ? 'Activo' : 'Inactivo'"
                        />
                    </td>
                    <td class="p-3.5">
                        <div class="flex items-center justify-end gap-1">
                            <button
                                type="button"
                                class="p-2 rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                title="Editar"
                                :aria-label="`Editar ${product.name}`"
                                @click="openEdit(product)"
                            >
                                <AppIcon :name="Pencil" :size="16" />
                            </button>
                            <button
                                type="button"
                                class="p-2 rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition"
                                title="Eliminar"
                                :aria-label="`Eliminar ${product.name}`"
                                @click="askDelete(product)"
                            >
                                <AppIcon :name="Trash2" :size="16" />
                            </button>
                        </div>
                    </td>
                </tr>
            </AppTable>

            <div
                v-if="filtered.length > 0"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm"
            >
                <AppPagination v-model:page="page" :total="filtered.length" :per-page="perPage" />
            </div>
        </div>

        <ProductFormModal :open="formOpen" :product="editing" @close="formOpen = false" />

        <ConfirmDialog
            :open="deleteOpen"
            danger
            title="Eliminar producto"
            :message="`¿Eliminar '${productToDelete?.name}'? El producto dejará de aparecer en el catálogo, pero su historial de ventas se conserva.`"
            confirm-text="Eliminar"
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="deleteOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import AppBadge from '../components/ui/AppBadge.vue';
import AppTable from '../components/ui/AppTable.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppPagination from '../components/ui/AppPagination.vue';
import ConfirmDialog from '../components/ui/ConfirmDialog.vue';
import ProductFormModal from '../components/ProductFormModal.vue';
import { useProductStore } from '../stores/products';
import { useToastStore } from '../stores/toast';
import { resolveCategoryIcon } from '../config/categoryIcons';

const productStore = useProductStore();
const toastStore = useToastStore();

const perPage = 10;
const search = ref('');
const categoryId = ref(null);
const statusFilter = ref('all');
const page = ref(1);
const formOpen = ref(false);
const editing = ref(null);
const deleteOpen = ref(false);
const productToDelete = ref(null);
const deleting = ref(false);

const formatMoney = (value) => Number(value || 0).toLocaleString('es-CO');
const imageUrl = (path) => `/storage/${path}`;
const categoryIcon = (product) => resolveCategoryIcon(product.category?.icon);

const load = async () => {
    try {
        await Promise.all([productStore.fetchProducts(), productStore.fetchCategories()]);
    } catch (err) {
        toastStore.error(productStore.error || 'No se pudo cargar el catálogo');
    }
};

onMounted(load);

watch([search, categoryId], () => {
    page.value = 1;

    productStore
        .fetchProducts({
            search: search.value || undefined,
            categoryId: categoryId.value || undefined,
        })
        .catch(() => {});
});

watch(statusFilter, () => {
    page.value = 1;
});

const filtered = computed(() => {
    if (statusFilter.value === 'all') {
        return productStore.items;
    }

    const wantActive = statusFilter.value === 'active';
    return productStore.items.filter((product) => product.is_active === wantActive);
});

const paged = computed(() => {
    const start = (page.value - 1) * perPage;
    return filtered.value.slice(start, start + perPage);
});

const openCreate = () => {
    editing.value = null;
    formOpen.value = true;
};

const openEdit = (product) => {
    editing.value = product;
    formOpen.value = true;
};

const askDelete = (product) => {
    productToDelete.value = product;
    deleteOpen.value = true;
};

const confirmDelete = async () => {
    deleting.value = true;

    try {
        await productStore.deleteProduct(productToDelete.value.id);
        toastStore.success('Producto eliminado');
        deleteOpen.value = false;
    } catch (err) {
        toastStore.error(err.response?.data?.message || 'No se pudo eliminar el producto');
    } finally {
        deleting.value = false;
    }
};
</script>

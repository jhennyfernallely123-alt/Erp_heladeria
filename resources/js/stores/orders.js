import { defineStore } from 'pinia';
import api from '../api';
import { useShiftStore } from './shifts';

export const useOrderStore = defineStore('orders', {
    state: () => ({
        activeOrder: null,
        cartItems: [],
        selectedTable: null,
        orderType: 'dine_in',
        orderNotes: '',
        tipAmount: 0,
        discountAmount: 0,

        // Datos de entrega. Solo se usan cuando orderType es 'delivery'.
        delivery: {
            name: '',
            phone: '',
            address: '',
            notes: '',
        },

        // Tarifa de envio vigente, leida de Configuracion. Es una estimacion
        // mientras se arma el pedido: al guardar manda el snapshot del servidor.
        deliveryFee: 0,

        loading: false,
    }),

    getters: {
        itemsCount: (state) => state.cartItems.reduce((acc, item) => acc + item.quantity, 0),
        subtotal: (state) => state.cartItems.reduce((acc, item) => acc + (item.unit_price * item.quantity), 0),
        isDelivery: (state) => state.orderType === 'delivery',

        /** Faltan datos para poder enviar el pedido. El mensaje va sin emojis. */
        deliveryMissing: (state) => {
            if (state.orderType !== 'delivery') return [];
            const required = [
                ['name', 'el nombre de quien recibe'],
                ['phone', 'el telefono'],
                ['address', 'la direccion'],
            ];
            return required
                .filter(([key]) => !String(state.delivery[key] || '').trim())
                .map(([, label]) => label);
        },

        total: (state) => {
            const sub = state.cartItems.reduce((acc, item) => acc + (item.unit_price * item.quantity), 0);
            const fee = state.orderType === 'delivery' ? Number(state.deliveryFee || 0) : 0;
            return Math.max(0, sub - Number(state.discountAmount || 0) + Number(state.tipAmount || 0) + fee);
        }
    },

    actions: {
        initNewOrder(table = null, type = 'dine_in') {
            this.selectedTable = table;
            this.orderType = type;
            this.cartItems = [];
            this.orderNotes = '';
            this.tipAmount = 0;
            this.discountAmount = 0;
            this.delivery = { name: '', phone: '', address: '', notes: '' };
            this.activeOrder = null;
        },

        /** Carga la tarifa vigente de Configuracion para el total en vivo. */
        async loadDeliveryFee() {
            try {
                const res = await api.get('/settings');
                this.deliveryFee = Number(res.data.data?.delivery_fee || 0);
            } catch (e) {
                this.deliveryFee = 0;
            }
        },

        loadExistingOrder(order) {
            this.activeOrder = order;
            this.selectedTable = order.table || null;
            this.orderType = order.type;
            this.orderNotes = order.notes || '';
            this.tipAmount = Number(order.tip_amount || 0);
            this.discountAmount = Number(order.discount_total || 0);
            this.delivery = {
                name: order.delivery_name || '',
                phone: order.delivery_phone || '',
                address: order.delivery_address || '',
                notes: order.delivery_notes || '',
            };
            if (order.type === 'delivery') {
                this.deliveryFee = Number(order.delivery_fee || 0);
            }
            this.cartItems = (order.items || []).map(i => ({
                product_id: i.product_id,
                product_variant_id: i.product_variant_id,
                name: i.product?.name + (i.variant ? ` (${i.variant.name})` : ''),
                quantity: Number(i.quantity),
                unit_price: Number(i.unit_price),
                notes: i.notes || '',
            }));
        },

        addItem(product, variant = null, quantity = 1, notes = '') {
            const variantId = variant ? variant.id : null;
            const price = variant ? Number(variant.sale_price) : Number(product.sale_price);
            const itemName = product.name + (variant ? ` (${variant.name})` : '');

            const existingIdx = this.cartItems.findIndex(
                item => item.product_id === product.id && item.product_variant_id === variantId
            );

            if (existingIdx !== -1) {
                this.cartItems[existingIdx].quantity += quantity;
                if (notes) {
                    this.cartItems[existingIdx].notes = notes;
                }
            } else {
                this.cartItems.push({
                    product_id: product.id,
                    product_variant_id: variantId,
                    name: itemName,
                    quantity: quantity,
                    unit_price: price,
                    notes: notes,
                });
            }
        },

        removeItem(index) {
            this.cartItems.splice(index, 1);
        },

        updateQuantity(index, qty) {
            if (qty <= 0) {
                this.removeItem(index);
            } else {
                this.cartItems[index].quantity = qty;
            }
        },

        async saveOrder() {
            this.loading = true;
            try {
                const isDelivery = this.orderType === 'delivery';

                // Se manda el turno activo del dispositivo para que la comanda
                // quede atribuida a quien estaba de turno. El servidor lo
                // valida: si no sirve, la comanda se guarda igual sin
                // atribucion.
                const shiftStore = useShiftStore();

                const payload = {
                    table_id: this.selectedTable?.id || null,
                    type: this.orderType,
                    notes: this.orderNotes,
                    tip_amount: this.tipAmount,
                    discount_total: this.discountAmount,
                    work_shift_id: shiftStore.activeShiftId,
                    items: this.cartItems.map(i => ({
                        product_id: i.product_id,
                        product_variant_id: i.product_variant_id,
                        quantity: i.quantity,
                        notes: i.notes,
                    })),
                };

                // El backend ignora estos campos si el tipo no es delivery, pero
                // no hace falta mandarlos en ese caso.
                if (isDelivery) {
                    payload.delivery_name = this.delivery.name.trim();
                    payload.delivery_phone = this.delivery.phone.trim();
                    payload.delivery_address = this.delivery.address.trim();
                    payload.delivery_notes = this.delivery.notes.trim() || null;
                }

                let res;
                if (this.activeOrder) {
                    res = await api.put(`/orders/${this.activeOrder.id}`, payload);
                } else {
                    res = await api.post('/orders', payload);
                }

                this.activeOrder = res.data.data;
                return this.activeOrder;
            } finally {
                this.loading = false;
            }
        }
    }
});

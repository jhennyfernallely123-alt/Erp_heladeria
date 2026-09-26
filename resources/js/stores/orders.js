import { defineStore } from 'pinia';
import api from '../api';

export const useOrderStore = defineStore('orders', {
    state: () => ({
        activeOrder: null,
        cartItems: [],
        selectedTable: null,
        orderType: 'dine_in',
        orderNotes: '',
        tipAmount: 0,
        discountAmount: 0,
        loading: false,
    }),

    getters: {
        itemsCount: (state) => state.cartItems.reduce((acc, item) => acc + item.quantity, 0),
        subtotal: (state) => state.cartItems.reduce((acc, item) => acc + (item.unit_price * item.quantity), 0),
        total: (state) => {
            const sub = state.cartItems.reduce((acc, item) => acc + (item.unit_price * item.quantity), 0);
            return Math.max(0, sub - Number(state.discountAmount || 0) + Number(state.tipAmount || 0));
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
            this.activeOrder = null;
        },

        loadExistingOrder(order) {
            this.activeOrder = order;
            this.selectedTable = order.table || null;
            this.orderType = order.type;
            this.orderNotes = order.notes || '';
            this.tipAmount = Number(order.tip_amount || 0);
            this.discountAmount = Number(order.discount_total || 0);
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
                const payload = {
                    table_id: this.selectedTable?.id || null,
                    type: this.orderType,
                    notes: this.orderNotes,
                    tip_amount: this.tipAmount,
                    discount_total: this.discountAmount,
                    items: this.cartItems.map(i => ({
                        product_id: i.product_id,
                        product_variant_id: i.product_variant_id,
                        quantity: i.quantity,
                        notes: i.notes,
                    })),
                };

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

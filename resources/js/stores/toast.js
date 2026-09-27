import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
    state: () => ({
        items: [],
    }),

    actions: {
        push(type, message, timeout = 4000) {
            const id = nextId++;
            this.items.push({ id, type, message });

            if (timeout > 0) {
                setTimeout(() => this.dismiss(id), timeout);
            }

            return id;
        },

        success(message) {
            return this.push('success', message);
        },

        error(message) {
            return this.push('error', message, 6000);
        },

        info(message) {
            return this.push('info', message);
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    },
});

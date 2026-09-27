import {
    LayoutDashboard,
    IceCreamBowl,
    Boxes,
    Wallet,
    BarChart3,
    Settings,
} from 'lucide-vue-next';

export const navItems = [
    { label: 'Inicio', route: '/', name: 'pos', icon: LayoutDashboard, roles: ['admin', 'cashier', 'waiter', 'kitchen'] },
    { label: 'Productos', route: '/productos', name: 'products', icon: IceCreamBowl, roles: ['admin', 'cashier'] },
    { label: 'Inventario', route: '/inventario', name: 'inventory', icon: Boxes, roles: ['admin'] },
    { label: 'Caja', route: '/caja', name: 'cash-register', icon: Wallet, roles: ['admin', 'cashier'] },
    { label: 'Reportes', route: '/reportes', name: 'reports', icon: BarChart3, roles: ['admin'] },
    { label: 'Configuración', route: '/configuracion', name: 'settings', icon: Settings, roles: ['admin'] },
];

export const visibleNavItems = (roles = []) =>
    navItems.filter((item) => item.roles.some((role) => roles.includes(role)));

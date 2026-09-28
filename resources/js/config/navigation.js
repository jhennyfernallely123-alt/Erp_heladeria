import {
    LayoutDashboard,
    IceCreamBowl,
    Boxes,
    Wallet,
    BarChart3,
    Settings,
    Clock,
    Users,
} from 'lucide-vue-next';

// El admin no opera el punto de venta ni la gaveta: administra el negocio.
// El POS es del cajero, mesero y cocina; abrir y cerrar caja es del cajero.
// El admin tiene su propia Caja (/caja/resumen), que es de supervision: mira el
// saldo de la gaveta y registra retiros.
export const navItems = [
    { label: 'Inicio', route: '/', name: 'pos', icon: LayoutDashboard, roles: ['cashier', 'waiter', 'kitchen'] },
    { label: 'Turnos', route: '/turnos', name: 'shifts', icon: Clock, roles: ['admin', 'waiter'] },
    { label: 'Productos', route: '/productos', name: 'products', icon: IceCreamBowl, roles: ['admin', 'cashier'] },
    { label: 'Inventario', route: '/inventario', name: 'inventory', icon: Boxes, roles: ['admin'] },
    { label: 'Caja', route: '/caja/resumen', name: 'cash-overview', icon: Wallet, roles: ['admin'] },
    { label: 'Caja', route: '/caja', name: 'cash-register', icon: Wallet, roles: ['cashier'] },
    { label: 'Equipo', route: '/empleados/equipo', name: 'team', icon: Users, roles: ['admin'] },
    { label: 'Reportes', route: '/reportes', name: 'reports', icon: BarChart3, roles: ['admin'] },
    { label: 'Configuración', route: '/configuracion', name: 'settings', icon: Settings, roles: ['admin'] },
];

export const visibleNavItems = (roles = []) =>
    navItems.filter((item) => item.roles.some((role) => roles.includes(role)));

/**
 * Pantalla de inicio segun el rol: el admin entra a turnos y el resto al POS.
 * Se usa para el redirect post-login, el route guard y el catch-all.
 */
export const homeRouteFor = (roles = []) => {
    const items = visibleNavItems(roles);
    return items.length > 0 ? items[0].route : '/login';
};

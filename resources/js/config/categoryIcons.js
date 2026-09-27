import {
    IceCreamBowl,
    GlassWater,
    Package,
    Coffee,
    Sparkles,
    CakeSlice,
} from 'lucide-vue-next';

// Los nombres vienen de categories.icon, que el seeder ya guarda en kebab-case.
// El mapa es explícito a propósito: resolver cadenas arbitrarias a componentes
// exigiría import() dinámico y rompería el bundle estático.
const categoryIconMap = {
    'ice-cream': IceCreamBowl,
    glass: GlassWater,
    box: Package,
    coffee: Coffee,
    sparkles: Sparkles,
    cake: CakeSlice,
};

export const resolveCategoryIcon = (icon) => categoryIconMap[icon] || IceCreamBowl;

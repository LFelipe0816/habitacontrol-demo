import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { UIProvider } from './context/UIContext';
import AppLayout from './Layouts/AppLayout';

createInertiaApp({
  title: t => (t ? `${t} · HabitaControl` : 'HabitaControl'),
  resolve: async name => {
    const page: any = await resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx'));
    // Layout persistente por defecto (Sidebar no se remonta entre visitas). Login lo desactiva con `layout = null`.
    if (page.default.layout === undefined) page.default.layout = (p: React.ReactNode) => <AppLayout>{p}</AppLayout>;
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<UIProvider><App {...props} /></UIProvider>);
  },
  progress: { color: '#5980a6' },
});

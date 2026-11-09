import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import GuestLayout from '@/layouts/guest-layout';

const appName = import.meta.env.VITE_APP_NAME || 'WebStudio OS';
const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
const cache = new Map<string, { default: React.ComponentType }>();

createInertiaApp({
  title: (title) => (title ? `${title} — ${appName}` : appName),
  resolve: (name) => {
    let page = cache.get(name);
    if (!page) {
      const loaded = pages[`./pages/${name}.tsx`] as { default: React.ComponentType } | undefined;
      if (!loaded) {
        throw new Error(`Página não encontrada: ${name}`);
      }
      (loaded.default as unknown as { layout?: unknown }).layout = name.startsWith('auth/') ? GuestLayout : AuthenticatedLayout;
      page = loaded;
      cache.set(name, page);
    }
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#4B5563',
  },
});

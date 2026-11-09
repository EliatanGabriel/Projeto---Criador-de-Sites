import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import GuestLayout from '@/layouts/guest-layout';

const appName = import.meta.env.VITE_APP_NAME || 'WebStudio OS';
const pages = import.meta.glob('./pages/**/*.tsx');

createInertiaApp({
  title: (title) => (title ? `${title} — ${appName}` : appName),
  async resolve(name) {
    const loader = pages[`./pages/${name}.tsx`] as
      | (() => Promise<{ default: React.ComponentType }>)
      | undefined;
    if (!loader) {
      throw new Error(`Página não encontrada: ${name}`);
    }
    const page = await loader();
    (page.default as unknown as { layout?: unknown }).layout = name.startsWith('auth/')
      ? GuestLayout
      : AuthenticatedLayout;
    return page.default;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#4B5563',
  },
});

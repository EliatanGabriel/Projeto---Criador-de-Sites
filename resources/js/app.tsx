import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'WebStudio OS';

createInertiaApp({
  title: (title) => (title ? `${title} — ${appName}` : appName),
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
    const page = pages[`./pages/${name}.tsx`] as { default: React.ComponentType };
    if (!page) {
      throw new Error(`Página não encontrada: ${name}`);
    }
    return page;
  },
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<App {...props} />);
  },
  progress: {
    color: '#4B5563',
  },
});

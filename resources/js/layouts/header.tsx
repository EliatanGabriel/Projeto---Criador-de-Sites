import { usePage, useForm } from '@inertiajs/react';
import { Menu, LogOut } from 'lucide-react';
import type { PageProps } from '@/types';
import { useState, useRef, useEffect } from 'react';

export default function Header({ onMenuClick }: { onMenuClick: () => void }) {
  const { auth } = usePage<PageProps>().props;
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  const logout = useForm({});

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b bg-white px-4 sm:px-6">
      <div className="flex items-center gap-3">
        <button
          onClick={onMenuClick}
          className="rounded-md p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
          aria-label="Abrir menu"
        >
          <Menu className="h-5 w-5" />
        </button>
        <h1 className="text-base font-semibold text-gray-900">WebStudio OS</h1>
        <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Fase 1</span>
      </div>

      <div className="relative" ref={ref}>
        <button
          onClick={() => setOpen(!open)}
          className="flex items-center gap-3 rounded-full p-1.5 hover:bg-gray-100"
          aria-expanded={open}
          aria-haspopup="true"
        >
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white">
            {auth.user?.name?.charAt(0).toUpperCase() ?? '?'}
          </div>
          <span className="hidden text-sm font-medium text-gray-700 sm:block">{auth.user?.name}</span>
        </button>
        {open && (
          <div className="absolute right-0 mt-2 w-48 rounded-md border bg-white py-1 shadow-lg">
            <div className="border-b px-4 py-2 text-sm text-gray-700">
              <p className="font-medium">{auth.user?.name}</p>
              <p className="text-xs text-gray-500">{auth.user?.email}</p>
            </div>
            <button
              className="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              onClick={() => logout.post('/logout')}
              disabled={logout.processing}
            >
              <LogOut className="h-4 w-4" /> {logout.processing ? 'Saindo...' : 'Sair'}
            </button>
          </div>
        )}
      </div>
    </header>
  );
}

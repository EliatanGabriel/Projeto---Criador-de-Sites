import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ThemeToggle from '@/components/theme-toggle';

export default function GuestLayout({ children }: { children: ReactNode }) {
  return (
    <div className="relative flex min-h-screen flex-col items-center justify-center bg-gray-50 dark:bg-gray-950 px-4 py-10 dark:bg-gray-950">
      <div className="absolute right-4 top-4">
        <ThemeToggle />
      </div>
      <div className="w-full max-w-md">
        <div className="mb-8 text-center">
          <Link href="/" className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
            WebStudio <span className="text-blue-600">OS</span>
          </Link>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">Plataforma interna de gestão e produção de sites</p>
        </div>
        <div className="rounded-xl border bg-white dark:bg-gray-900 p-6 shadow-sm sm:p-8">{children}</div>
        <p className="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">Acesso restrito à equipe autorizada.</p>
      </div>
    </div>
  );
}

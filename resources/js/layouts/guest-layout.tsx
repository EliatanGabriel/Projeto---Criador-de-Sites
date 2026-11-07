import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export default function GuestLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-10">
      <div className="w-full max-w-md">
        <div className="mb-8 text-center">
          <Link href="/" className="text-2xl font-bold tracking-tight text-gray-900">
            WebStudio <span className="text-blue-600">OS</span>
          </Link>
          <p className="mt-2 text-sm text-gray-600">Plataforma interna de gestão e produção de sites</p>
        </div>
        <div className="rounded-xl border bg-white p-6 shadow-sm sm:p-8">{children}</div>
        <p className="mt-6 text-center text-xs text-gray-500">Acesso restrito à equipe autorizada.</p>
      </div>
    </div>
  );
}

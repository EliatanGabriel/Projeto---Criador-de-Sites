import { useState, type ReactNode } from 'react';
import Sidebar from './sidebar';
import Header from './header';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, AlertCircle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function AuthenticatedLayout({ children }: { children: ReactNode }) {
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const { flash } = usePage<PageProps>().props;

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar
        collapsed={collapsed}
        onToggle={() => setCollapsed(!collapsed)}
        mobileOpen={mobileOpen}
        onMobileClose={() => setMobileOpen(false)}
      />
      <div className={cn('flex min-h-screen flex-col transition-all duration-200', collapsed ? 'lg:pl-16' : 'lg:pl-64')}>
        <Header onMenuClick={() => setMobileOpen(true)} />
        <main className="flex-1 p-4 sm:p-6 lg:p-8" role="main">
          <div className="mx-auto max-w-7xl">
            {flash.success && (
              <Alert className="mb-6" variant="success">
                <CheckCircle2 className="h-4 w-4" />
                <AlertTitle>Sucesso</AlertTitle>
                <AlertDescription>{flash.success}</AlertDescription>
              </Alert>
            )}
            {flash.error && (
              <Alert className="mb-6" variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>Erro</AlertTitle>
                <AlertDescription>{flash.error}</AlertDescription>
              </Alert>
            )}
            {flash.info && (
              <Alert className="mb-6" variant="default">
                <Info className="h-4 w-4" />
                <AlertTitle>Informação</AlertTitle>
                <AlertDescription>{flash.info}</AlertDescription>
              </Alert>
            )}
            {children}
          </div>
        </main>
        <footer className="border-t bg-white px-6 py-4 text-xs text-gray-500">
          WebStudio OS — Fase 1 (Fundação técnica). Acesso interno da equipe.
        </footer>
      </div>
    </div>
  );
}

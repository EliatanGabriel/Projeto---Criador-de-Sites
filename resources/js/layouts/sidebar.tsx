import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users, Briefcase, FileText, FolderKanban, CheckSquare, Layers, Globe, Wallet, BarChart3, Settings, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

const NAV_ITEMS = [
  { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, active: true, disabled: false },
  { label: 'Comercial', href: '#', icon: Briefcase, active: false, disabled: true },
  { label: 'Clientes', href: '#', icon: Users, active: false, disabled: true },
  { label: 'Demandas', href: '#', icon: FileText, active: false, disabled: true },
  { label: 'Projetos', href: '#', icon: FolderKanban, active: false, disabled: true },
  { label: 'Tarefas', href: '#', icon: CheckSquare, active: false, disabled: true },
  { label: 'Templates', href: '#', icon: Layers, active: false, disabled: true },
  { label: 'Sites publicados', href: '#', icon: Globe, active: false, disabled: true },
  { label: 'Financeiro', href: '#', icon: Wallet, active: false, disabled: true },
  { label: 'Relatórios', href: '#', icon: BarChart3, active: false, disabled: true },
  { label: 'Equipe', href: '#', icon: Users, active: false, disabled: true },
  { label: 'Configurações', href: '#', icon: Settings, active: false, disabled: true },
];

interface SidebarProps {
  collapsed: boolean;
  onToggle: () => void;
  mobileOpen: boolean;
  onMobileClose: () => void;
}

export default function Sidebar({ collapsed, onToggle, mobileOpen, onMobileClose }: SidebarProps) {
  const { auth } = usePage<PageProps>().props;

  return (
    <>
      {mobileOpen && (
        <div className="fixed inset-0 z-40 bg-black/50 lg:hidden" onClick={onMobileClose} aria-hidden="true" />
      )}
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-50 flex flex-col bg-gray-900 text-white transition-all duration-200',
          'w-64 lg:fixed',
          collapsed && 'lg:w-16',
          mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        )}
      >
        <div className="flex h-16 items-center justify-between border-b border-white/10 px-4">
          {!collapsed && (
            <Link href="/dashboard" className="flex-1 text-lg font-bold tracking-tight">
              WebStudio <span className="text-blue-400">OS</span>
            </Link>
          )}
          {collapsed && (
            <Link href="/dashboard" className="mx-auto text-xl font-bold text-blue-400">
              W
            </Link>
          )}
          <div className="flex items-center gap-1">
            <button
              onClick={onToggle}
              className="hidden rounded-md p-1.5 text-gray-300 hover:bg-white/10 lg:block"
              aria-label={collapsed ? 'Expandir menu' : 'Recolher menu'}
            >
              {collapsed ? <ChevronRight className="h-5 w-5" /> : <ChevronLeft className="h-5 w-5" />}
            </button>
            <button
              onClick={onMobileClose}
              className="rounded-md p-1.5 text-gray-300 hover:bg-white/10 lg:hidden"
              aria-label="Fechar menu"
            >
              <X className="h-5 w-5" />
            </button>
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto p-3">
          <ul className="space-y-1">
            {NAV_ITEMS.map((item) => {
              const Icon = item.icon;
              const content = (
                <>
                  <Icon className={cn('h-5 w-5 shrink-0', collapsed && 'mx-auto')} />
                  {!collapsed && <span className="flex-1 truncate text-left">{item.label}</span>}
                  {!collapsed && item.disabled && (
                    <span className="rounded-full bg-white/10 px-2 py-0.5 text-[10px] uppercase tracking-wide text-gray-400">
                      Em breve
                    </span>
                  )}
                </>
              );
              return (
                <li key={item.label}>
                  {item.disabled ? (
                    <span
                      title={collapsed ? item.label : undefined}
                      className={cn(
                        'flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-gray-500',
                        collapsed && 'justify-center px-2'
                      )}
                    >
                      {content}
                    </span>
                  ) : (
                    <Link
                      href={item.href}
                      title={collapsed ? item.label : undefined}
                      className={cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                        item.active
                          ? 'bg-blue-600 text-white'
                          : 'text-gray-300 hover:bg-white/10 hover:text-white',
                        collapsed && 'justify-center px-2'
                      )}
                    >
                      {content}
                    </Link>
                  )}
                </li>
              );
            })}
          </ul>

          <div className={cn('mt-6 rounded-lg border border-white/10 p-3 text-xs text-gray-400', collapsed && 'hidden')}>
            <p className="font-medium text-gray-300">Fase 1 — Fundação</p>
            <p className="mt-1 leading-relaxed">Módulos de negócio em breve. Estrutura, autenticação e permissões disponíveis.</p>
          </div>
        </nav>

        <div className="border-t border-white/10 p-3">
          <div className={cn('flex items-center gap-3', collapsed && 'justify-center')}>
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold">
              {auth.user?.name?.charAt(0).toUpperCase() ?? '?'}
            </div>
            {!collapsed && (
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{auth.user?.name ?? 'Usuário'}</p>
                <p className="truncate text-xs text-gray-400">{auth.user?.roles?.join(', ') ?? ''}</p>
              </div>
            )}
          </div>
        </div>
      </aside>
    </>
  );
}

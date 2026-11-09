import { memo } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, Users, Briefcase, FileText, FolderKanban, CheckSquare, Layers, Globe, Wallet, BarChart3, Settings, ChevronLeft, ChevronRight, X, Bell, User } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface SidebarProps {
  collapsed: boolean;
  onToggle: () => void;
  mobileOpen: boolean;
  onMobileClose: () => void;
}

function SidebarBase({ collapsed, onToggle, mobileOpen, onMobileClose }: SidebarProps) {
  const { auth } = usePage<PageProps>().props;
  const permissions = auth.user?.permissions ?? [];
  const can = (p: string) => permissions.includes(p);

  const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

  const roles = auth.user?.roles ?? [];

  const items = [
    { label: 'Painel Admin', href: '/admin', icon: LayoutDashboard, visible: roles.includes('OWNER'), active: currentPath === '/admin', disabled: false },
    { label: 'Painel Comercial', href: '/commercial', icon: Briefcase, visible: roles.includes('COMMERCIAL') || roles.includes('OWNER'), active: currentPath === '/commercial', disabled: false },
    { label: 'Painel Developer', href: '/developer', icon: FolderKanban, visible: roles.includes('DEVELOPER') || roles.includes('OWNER'), active: currentPath === '/developer', disabled: false },
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, visible: can('view-dashboard'), active: currentPath === '/dashboard', disabled: false },
    { label: 'Comercial', href: '/opportunities', icon: Briefcase, visible: can('commercial.view'), active: currentPath.startsWith('/opportunities'), disabled: false },
    { label: 'Clientes', href: '/clients', icon: Users, visible: can('clients.view'), active: currentPath.startsWith('/clients'), disabled: false },
    { label: 'Demandas', href: '/demands', icon: FileText, visible: can('demands.view'), active: currentPath.startsWith('/demands'), disabled: false },
    { label: 'Projetos', href: '/projects', icon: FolderKanban, visible: can('projects.view'), active: currentPath.startsWith('/projects'), disabled: false },
    { label: 'Tarefas', href: '/tasks', icon: CheckSquare, visible: can('tasks.view'), active: currentPath.startsWith('/tasks'), disabled: false },
    { label: 'Templates', href: '/templates', icon: Layers, visible: can('templates.view'), active: currentPath.startsWith('/templates'), disabled: false },
    { label: 'Sites', href: '/sites', icon: Globe, visible: can('websites.view'), active: currentPath.startsWith('/sites'), disabled: false },
    { label: 'Alertas', href: '/alerts', icon: Bell, visible: can('alerts.view'), active: currentPath.startsWith('/alerts'), disabled: false },
    { label: 'Financeiro', href: '/finance', icon: Wallet, visible: can('finance.view'), active: currentPath.startsWith('/finance'), disabled: false },
    { label: 'Relatórios', href: '/finance/report', icon: BarChart3, visible: can('reports.view') || can('finance.view'), active: currentPath.startsWith('/finance/report'), disabled: false },
    { label: 'Meu Perfil', href: '/profile', icon: User, visible: true, active: currentPath.startsWith('/profile'), disabled: false },
    { label: 'Equipe', href: '/team', icon: Users, visible: can('users.view'), active: currentPath.startsWith('/team'), disabled: false },
    { label: 'Configurações', href: '/settings', icon: Settings, visible: can('settings.view'), active: currentPath.startsWith('/settings'), disabled: false },
  ].filter((i) => i.visible);

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
            {items.map((item) => {
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
                      prefetch
                      title={collapsed ? item.label : undefined}
                      className={cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                        item.active ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white',
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
            <p className="font-medium text-gray-300">Fase 2 — Gestão</p>
            <p className="mt-1 leading-relaxed">Usuários, clientes e oportunidades disponíveis.</p>
          </div>
        </nav>

        <div className="border-t border-white/10 p-3">
          <Link href="/profile" title="Meu Perfil" className={cn('flex items-center gap-3 rounded-lg p-1 hover:bg-white/10', collapsed && 'justify-center')}>
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold">
              {auth.user?.name?.charAt(0).toUpperCase() ?? '?'}
            </div>
            {!collapsed && (
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{auth.user?.name ?? 'Usuário'}</p>
                <p className="truncate text-xs text-gray-400">{auth.user?.email ?? ''}</p>
                <p className="truncate text-xs text-gray-500">{auth.user?.roles?.join(', ') ?? ''}</p>
              </div>
            )}
          </Link>
        </div>
      </aside>
    </>
  );
}

const Sidebar = memo(SidebarBase);
export default Sidebar;

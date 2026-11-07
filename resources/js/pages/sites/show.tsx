import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function SitesShow({ site, healthStates, latestChecks, canManage, canPublish, canMaintenance, canCredentials, hostings, users, projects }: { site: any; healthStates: any[]; latestChecks: any[]; canManage: boolean; canPublish: boolean; canMaintenance: boolean; canCredentials: boolean; hostings: any[]; users: { id: string; name: string }[]; projects: { id: string; title: string }[] }) {
  const [tab, setTab] = useState('overview');
  const domainForm = useForm({ domain: '', registrar: '', registered_at: '', expires_at: '', status: 'ativo', is_primary: false, dns_notes: '' });
  const envForm = useForm({ name: '', type: 'production', url: '', hosting_id: '', status: 'ativo', notes: '' });
  const repoForm = useForm({ name: '', url: '', provider: 'github', default_branch: 'main', environment_id: '', notes: '' });
  const pubForm = useForm({ environment_id: '', version: '', published_at: new Date().toISOString().slice(0, 16), responsible_id: '', description: '', commit_reference: '', result: 'sucesso', notes: '' });
  const maintForm = useForm({ type: 'corrigir', description: '', responsible_id: '', status: 'aberta', opened_at: new Date().toISOString().slice(0, 10), project_id: '', task_id: '', notes: '' });
  const credForm = useForm({ website_id: site.id, hosting_id: '', domain_id: '', environment_id: '', type: 'cms', label: '', username: '', secret: '', notes: '' });

  const tabs = [
    ['overview', 'Visão geral'],
    ['domains', 'Domínios'],
    ['envs', 'Ambientes'],
    ['repos', 'Repositórios'],
    ['pubs', 'Publicações'],
    ['maint', 'Manutenção'],
    ['creds', 'Acesso'],
    ['health', 'Saúde'],
  ];

  return (
    <AuthenticatedLayout>
      <Head title={site.name} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">{site.name}</h2>
          <p className="mt-1 text-sm text-gray-600">
            {site.client?.name ?? '—'} • {site.status} • {site.type ?? '—'}
          </p>
        </div>
        {canManage && (
          <Link href={`/sites/${site.id}/edit`}>
            <Button>Editar site</Button>
          </Link>
        )}
      </div>

      <div className="mb-4 flex flex-wrap gap-2">
        {tabs.map(([k, l]) => (
          <button key={k} onClick={() => setTab(k)} className={`rounded-full px-3 py-1.5 text-sm ${tab === k ? 'bg-gray-900 text-white' : 'bg-gray-100'}`}>
            {l}
          </button>
        ))}
      </div>

      {tab === 'overview' && (
        <Card>
          <CardHeader>
            <CardTitle>Visão geral</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Descrição:</span> {site.description ?? '—'}</p>
            <p><span className="font-medium">Início:</span> {site.started_at ? new Date(site.started_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Publicado em:</span> {site.published_at ? new Date(site.published_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Técnico:</span> {site.technical_owner?.name ?? '—'}</p>
            <p><span className="font-medium">Tecnologias:</span> {(site.technologies ?? []).join(', ') || '—'}</p>
            <p><span className="font-medium">Observações:</span> {site.notes ?? '—'}</p>
            <div className="pt-3">
              <p className="font-medium">Projetos vinculados ({site.projects?.length ?? 0})</p>
              <ul className="mt-1 space-y-1">
                {(site.projects ?? []).map((p: any) => (
                  <li key={p.id}>
                    <Link href={`/projects/${p.id}`} className="text-blue-600 hover:underline">
                      {p.title}
                    </Link>{' '}
                    <span className="text-xs text-gray-500">({p.status})</span>
                  </li>
                ))}
              </ul>
            </div>
          </CardContent>
        </Card>
      )}

      {tab === 'domains' && (
        <Card>
          <CardHeader>
            <CardTitle>Domínios</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {canManage && (
              <form
                className="grid grid-cols-1 gap-3 sm:grid-cols-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  domainForm.post(`/sites/${site.id}/domains`);
                }}
              >
                <div className="space-y-2">
                  <Label>Domínio *</Label>
                  <Input value={domainForm.data.domain} onChange={(e) => domainForm.setData('domain', e.target.value)} placeholder="loja.exemplo.com.br" required />
                </div>
                <div className="space-y-2">
                  <Label>Registrador</Label>
                  <Input value={domainForm.data.registrar} onChange={(e) => domainForm.setData('registrar', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Vencimento</Label>
                  <Input type="date" value={domainForm.data.expires_at} onChange={(e) => domainForm.setData('expires_at', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Status</Label>
                  <select value={domainForm.data.status} onChange={(e) => domainForm.setData('status', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="ativo">Ativo</option>
                    <option value="suspenso">Suspenso</option>
                    <option value="transferido">Transferido</option>
                    <option value="expirado">Expirado</option>
                  </select>
                </div>
                <label className="flex items-center gap-2 text-sm">
                  <input type="checkbox" checked={domainForm.data.is_primary} onChange={(e) => domainForm.setData('is_primary', e.target.checked)} />
                  Domínio principal
                </label>
                <Button type="submit" disabled={domainForm.processing}>
                  Adicionar domínio
                </Button>
              </form>
            )}
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600">
                  <th className="pb-2">Domínio</th>
                  <th className="pb-2">Principal</th>
                  <th className="pb-2">Vencimento</th>
                  <th className="pb-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {(site.domains ?? []).map((d: any) => (
                  <tr key={d.id} className="border-b last:border-0">
                    <td className="py-2 font-medium">{d.domain}</td>
                    <td className="py-2">{d.is_primary ? 'Sim' : '—'}</td>
                    <td className="py-2">{d.expires_at ? new Date(d.expires_at).toLocaleDateString('pt-BR') : '—'}</td>
                    <td className="py-2">{d.status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>
      )}

      {tab === 'envs' && (
        <Card>
          <CardHeader>
            <CardTitle>Ambientes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {canManage && (
              <form
                className="grid grid-cols-1 gap-3 sm:grid-cols-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  envForm.post(`/sites/${site.id}/environments`);
                }}
              >
                <div className="space-y-2">
                  <Label>Nome *</Label>
                  <Input value={envForm.data.name} onChange={(e) => envForm.setData('name', e.target.value)} required placeholder="Produção" />
                </div>
                <div className="space-y-2">
                  <Label>Tipo *</Label>
                  <select value={envForm.data.type} onChange={(e) => envForm.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="development">Desenvolvimento</option>
                    <option value="staging">Homologação</option>
                    <option value="production">Produção</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label>URL</Label>
                  <Input value={envForm.data.url} onChange={(e) => envForm.setData('url', e.target.value)} placeholder="https://app.exemplo.com" />
                </div>
                <div className="space-y-2">
                  <Label>Hospedagem</Label>
                  <select value={envForm.data.hosting_id} onChange={(e) => envForm.setData('hosting_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="">— Sem hospedagem —</option>
                    {hostings.map((h: any) => (
                      <option key={h.id} value={h.id}>
                        {h.name} ({h.provider})
                      </option>
                    ))}
                  </select>
                </div>
                <Button type="submit" disabled={envForm.processing}>
                  Adicionar ambiente
                </Button>
              </form>
            )}
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600">
                  <th className="pb-2">Nome</th>
                  <th className="pb-2">Tipo</th>
                  <th className="pb-2">URL</th>
                  <th className="pb-2">Hospedagem</th>
                  <th className="pb-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {(site.environments ?? []).map((e: any) => (
                  <tr key={e.id} className="border-b last:border-0">
                    <td className="py-2 font-medium">{e.name}</td>
                    <td className="py-2">{e.type}</td>
                    <td className="py-2">{e.url ?? '—'}</td>
                    <td className="py-2">{e.hosting?.name ?? '—'}</td>
                    <td className="py-2">{e.status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>
      )}

      {tab === 'repos' && (
        <Card>
          <CardHeader>
            <CardTitle>Repositórios</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {canManage && (
              <form
                className="grid grid-cols-1 gap-3 sm:grid-cols-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  repoForm.post(`/sites/${site.id}/repositories`);
                }}
              >
                <div className="space-y-2">
                  <Label>Nome *</Label>
                  <Input value={repoForm.data.name} onChange={(e) => repoForm.setData('name', e.target.value)} required />
                </div>
                <div className="space-y-2">
                  <Label>URL *</Label>
                  <Input value={repoForm.data.url} onChange={(e) => repoForm.setData('url', e.target.value)} required placeholder="https://github.com/..." />
                </div>
                <div className="space-y-2">
                  <Label>Provedor</Label>
                  <select value={repoForm.data.provider} onChange={(e) => repoForm.setData('provider', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="github">GitHub</option>
                    <option value="gitlab">GitLab</option>
                    <option value="bitbucket">Bitbucket</option>
                    <option value="outros">Outros</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label>Branch principal</Label>
                  <Input value={repoForm.data.default_branch} onChange={(e) => repoForm.setData('default_branch', e.target.value)} />
                </div>
                <Button type="submit" disabled={repoForm.processing}>
                  Adicionar repositório
                </Button>
              </form>
            )}
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600">
                  <th className="pb-2">Nome</th>
                  <th className="pb-2">GitHub</th>
                  <th className="pb-2">Branch</th>
                  <th className="pb-2">Último commit</th>
                  <th className="pb-2">PRs abertos</th>
                  <th className="pb-2">Sincronização</th>
                  {canManage && <th className="pb-2">Ação</th>}
                </tr>
              </thead>
              <tbody>
                {(site.repositories ?? []).map((r: any) => (
                  <tr key={r.id} className="border-b last:border-0 align-top">
                    <td className="py-2 font-medium">{r.name}</td>
                    <td className="py-2">
                      <a href={r.url} target="_blank" className="text-blue-600 hover:underline">
                        {r.github_full_name ?? r.url}
                      </a>
                    </td>
                    <td className="py-2">{r.default_branch ?? '—'}</td>
                    <td className="py-2">
                      {r.last_commit_sha ? (
                        <>
                          <code>{r.last_commit_sha.slice(0, 7)}</code>
                          {r.last_commit_at && <span className="text-gray-500"> • {new Date(r.last_commit_at).toLocaleDateString('pt-BR')}</span>}
                        </>
                      ) : (
                        '—'
                      )}
                    </td>
                    <td className="py-2">{r.open_pull_requests_count ?? '—'}</td>
                    <td className="py-2 text-xs">
                      {r.last_sync_status === 'ok' ? (
                        <span className="text-green-700">OK {r.last_synced_at ? new Date(r.last_synced_at).toLocaleDateString('pt-BR') : ''}</span>
                      ) : r.last_sync_status ? (
                        <span className="text-red-600">{r.last_sync_error ?? 'Erro'}</span>
                      ) : (
                        <span className="text-gray-500">Não sincronizado</span>
                      )}
                      {r.last_sync_attempt_at && <div className="text-gray-400">{new Date(r.last_sync_attempt_at).toLocaleString('pt-BR')}</div>}
                    </td>
                    {canManage && (
                      <td className="py-2">
                        <form onSubmit={(e) => {
                          e.preventDefault();
                          useForm({}).post(`/sites/${site.id}/repositories/${r.id}/sync`);
                        }}>
                          <Button type="submit" variant="outline" size="sm">Sincronizar</Button>
                        </form>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
            {(site.repositories ?? []).length === 0 && <p className="text-sm text-gray-500">Nenhum repositório cadastrado.</p>}
          </CardContent>
        </Card>
      )}

      {tab === 'pubs' && (
        <Card>
          <CardHeader>
            <CardTitle>Publicações</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {canPublish && (
              <form
                className="grid grid-cols-1 gap-3 sm:grid-cols-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  pubForm.post(`/sites/${site.id}/publications`);
                }}
              >
                <div className="space-y-2">
                  <Label>Ambiente *</Label>
                  <select value={pubForm.data.environment_id} onChange={(e) => pubForm.setData('environment_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm" required>
                    <option value="">— Selecione —</option>
                    {(site.environments ?? []).map((en: any) => (
                      <option key={en.id} value={en.id}>
                        {en.name} ({en.type})
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <Label>Versão</Label>
                  <Input value={pubForm.data.version} onChange={(e) => pubForm.setData('version', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Resultado *</Label>
                  <select value={pubForm.data.result} onChange={(e) => pubForm.setData('result', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="sucesso">Sucesso</option>
                    <option value="falha">Falha</option>
                    <option value="rollback">Rollback</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label>Commit</Label>
                  <Input value={pubForm.data.commit_reference} onChange={(e) => pubForm.setData('commit_reference', e.target.value)} />
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label>Descrição</Label>
                  <Input value={pubForm.data.description} onChange={(e) => pubForm.setData('description', e.target.value)} />
                </div>
                <Button type="submit" disabled={pubForm.processing}>
                  Registrar publicação
                </Button>
              </form>
            )}
            <ul className="space-y-2 text-sm">
              {(site.publications ?? []).map((p: any) => (
                <li key={p.id} className="border-b pb-2">
                  <p className="font-medium">
                    {p.version ?? 'Sem versão'} — {p.result} — {new Date(p.published_at).toLocaleString('pt-BR')}
                  </p>
                  <p className="text-gray-600">{p.description ?? ''}</p>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}

      {tab === 'maint' && (
        <Card>
          <CardHeader>
            <CardTitle>Manutenção</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {canMaintenance && (
              <form
                className="grid grid-cols-1 gap-3 sm:grid-cols-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  maintForm.post(`/sites/${site.id}/maintenance`);
                }}
              >
                <div className="space-y-2">
                  <Label>Tipo *</Label>
                  <select value={maintForm.data.type} onChange={(e) => maintForm.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="corrigir">Corrigir</option>
                    <option value="evoluir">Evoluir</option>
                    <option value="seguranca">Segurança</option>
                    <option value="performance">Performance</option>
                    <option value="conteudo">Conteúdo</option>
                    <option value="outro">Outro</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label>Status *</Label>
                  <select value={maintForm.data.status} onChange={(e) => maintForm.setData('status', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="aberta">Aberta</option>
                    <option value="em_andamento">Em andamento</option>
                    <option value="concluida">Concluída</option>
                    <option value="cancelada">Cancelada</option>
                  </select>
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label>Descrição *</Label>
                  <textarea value={maintForm.data.description} onChange={(e) => maintForm.setData('description', e.target.value)} className="w-full rounded-md border p-2 text-sm" rows={2} required />
                </div>
                <div className="space-y-2">
                  <Label>Projeto (opcional)</Label>
                  <select value={maintForm.data.project_id} onChange={(e) => maintForm.setData('project_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="">— Sem projeto —</option>
                    {(site.projects ?? []).map((p: any) => (
                      <option key={p.id} value={p.id}>
                        {p.title}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <Label>Responsável</Label>
                  <select value={maintForm.data.responsible_id} onChange={(e) => maintForm.setData('responsible_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                    <option value="">— Selecione —</option>
                    {users.map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name}
                      </option>
                    ))}
                  </select>
                </div>
                <Button type="submit" disabled={maintForm.processing}>
                  Registrar manutenção
                </Button>
              </form>
            )}
            <ul className="space-y-2 text-sm">
              {(site.maintenance_records ?? []).map((m: any) => (
                <li key={m.id} className="border-b pb-2">
                  <p className="font-medium">
                    {m.type} — {m.status} — {m.opened_at ? new Date(m.opened_at).toLocaleDateString('pt-BR') : ''}
                  </p>
                  <p className="text-gray-600">{m.description}</p>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}

      {tab === 'creds' && (
        <Card>
          <CardHeader>
            <CardTitle>Acesso seguro (credenciais)</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {!canCredentials && <p className="text-sm text-red-600">Acesso restrito a OWNER. Credenciais não visíveis para seu perfil.</p>}
            {canCredentials && (
              <>
                <form
                  className="grid grid-cols-1 gap-3 sm:grid-cols-2"
                  onSubmit={(e) => {
                    e.preventDefault();
                    credForm.post('/credentials');
                  }}
                >
                  <div className="space-y-2">
                    <Label>Rótulo *</Label>
                    <Input value={credForm.data.label} onChange={(e) => credForm.setData('label', e.target.value)} required placeholder="Painel CMS" />
                  </div>
                  <div className="space-y-2">
                    <Label>Tipo *</Label>
                    <select value={credForm.data.type} onChange={(e) => credForm.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                      <option value="hosting_admin">Hospedagem admin</option>
                      <option value="registrar">Registrador</option>
                      <option value="ftp">FTP</option>
                      <option value="cms">CMS</option>
                      <option value="service_token">Token de serviço</option>
                      <option value="outro">Outro</option>
                    </select>
                  </div>
                  <div className="space-y-2">
                    <Label>Usuário</Label>
                    <Input value={credForm.data.username} onChange={(e) => credForm.setData('username', e.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <Label>Segredo * (criptografado em repouso)</Label>
                    <Input type="password" value={credForm.data.secret} onChange={(e) => credForm.setData('secret', e.target.value)} required />
                  </div>
                  <Button type="submit" disabled={credForm.processing}>
                    Salvar credencial
                  </Button>
                </form>
                <div>
                  <p className="mb-2 text-sm font-medium">Credenciais registradas (mascaradas)</p>
                  <ul className="space-y-2 text-sm">
                    {(site.credentials ?? []).map((c: any) => (
                      <li key={c.id} className="flex items-center justify-between border-b pb-2">
                        <span>
                          <b>{c.label}</b> • {c.username ?? '—'} • ••••••••
                        </span>
                        <span className="text-xs text-gray-500">acesso auditado</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </>
            )}
          </CardContent>
        </Card>
      )}

      {tab === 'health' && (
        <Card>
          <CardHeader>
            <CardTitle>Saúde operacional</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {canManage && (
              <form onSubmit={(e) => {
                e.preventDefault();
                useForm({}).post(`/sites/${site.id}/health-check`);
              }}>
                <Button type="submit" variant="outline" size="sm">Executar verificação manual</Button>
              </form>
            )}
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600">
                  <th className="pb-2">Domínio</th>
                  <th className="pb-2">Estado</th>
                  <th className="pb-2">Falhas</th>
                  <th className="pb-2">Última verificação</th>
                  <th className="pb-2">Recuperado em</th>
                </tr>
              </thead>
              <tbody>
                {(healthStates ?? []).map((s: any) => (
                  <tr key={s.domain_id} className="border-b last:border-0">
                    <td className="py-2 font-medium">{s.domain}</td>
                    <td className="py-2"><span className={s.state === 'operational' ? 'text-green-700' : s.state === 'down' ? 'text-red-600' : 'text-amber-600'}>{s.state}</span></td>
                    <td className="py-2">{s.consecutive_failures}</td>
                    <td className="py-2">{s.last_checked_at ? new Date(s.last_checked_at).toLocaleString('pt-BR') : '—'}</td>
                    <td className="py-2">{s.last_recovered_at ? new Date(s.last_recovered_at).toLocaleString('pt-BR') : '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            {(healthStates ?? []).length === 0 && <p className="text-sm text-gray-500">Nenhuma verificação de saúde ainda.</p>}
            <div>
              <p className="mb-2 text-sm font-medium">Últimas verificações</p>
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-gray-600">
                    <th className="pb-2">Início</th>
                    <th className="pb-2">Domínio</th>
                    <th className="pb-2">Origem</th>
                    <th className="pb-2">HTTP</th>
                    <th className="pb-2">Duração</th>
                    <th className="pb-2">SSL</th>
                    <th className="pb-2">Erro</th>
                  </tr>
                </thead>
                <tbody>
                  {(latestChecks ?? []).map((c: any) => (
                    <tr key={c.id} className="border-b last:border-0">
                      <td className="py-2">{c.checked_at ? new Date(c.checked_at).toLocaleString('pt-BR') : '—'}</td>
                      <td className="py-2">{c.domain ?? '—'}</td>
                      <td className="py-2">{c.trigger === 'scheduled' ? 'Agendada' : 'Manual'}</td>
                      <td className="py-2">{c.ok ? <span className="text-green-700">OK{c.http_status ? ` (${c.http_status})` : ''}</span> : <span className="text-red-600">Falha{c.http_status ? ` (${c.http_status})` : ''}</span>}</td>
                      <td className="py-2">{c.latency_ms != null ? `${c.latency_ms} ms` : '—'}</td>
                      <td className="py-2">{c.ssl_valid == null ? '—' : c.ssl_valid ? 'Válido' : 'Inválido'}{c.ssl_expires_at ? ` • ${new Date(c.ssl_expires_at).toLocaleDateString('pt-BR')}` : ''}</td>
                      <td className="py-2 text-xs">{c.error_code ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </AuthenticatedLayout>
  );
}

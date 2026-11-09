import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function DemandsCreate({ clients, opportunities }: { clients: { id: string; name: string }[]; opportunities: { id: string; title: string; client_id: string }[] }) {
  const form = useForm({
    title: '',
    client_id: '',
    opportunity_id: '',
    service_type: '',
    description: '',
    contracted_scope: '',
    negotiated_value: '',
    contracted_at: '',
    desired_deadline: '',
    priority: 'normal',
    internal_notes: '',
    briefing: {
      objectives: '',
      target_audience: '',
      business_description: '',
      products_services: '',
      desired_pages: '',
      desired_features: '',
      references: '',
      visual_identity: '',
      colors_preferences: '',
      available_content: '',
      domain_info: '',
      hosting_info: '',
      additional_notes: '',
    },
    files: [] as File[],
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((data) => ({
      ...data,
      files: data.files.length ? data.files : undefined,
    }));
    form.post('/demands', { forceFormData: true });
  }

  const filteredOpps = opportunities.filter((o) => !form.data.client_id || o.client_id === form.data.client_id);

  return (
    <>
      <Head title="Nova demanda" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Nova demanda</h2>
      <form onSubmit={submit} className="space-y-6">
        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Informações gerais</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="title">Título *</Label>
              <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
              {form.errors.title && <p className="text-sm text-red-600">{form.errors.title}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="client_id">Cliente *</Label>
              <select id="client_id" value={form.data.client_id} onChange={(e) => form.setData('client_id', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" required>
                <option value="">— Selecione —</option>
                {clients.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
              {form.errors.client_id && <p className="text-sm text-red-600">{form.errors.client_id}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="opportunity_id">Oportunidade (opcional)</Label>
              <select id="opportunity_id" value={form.data.opportunity_id} onChange={(e) => form.setData('opportunity_id', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="">— Sem oportunidade —</option>
                {filteredOpps.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.title}
                  </option>
                ))}
              </select>
              {form.errors.opportunity_id && <p className="text-sm text-red-600">{form.errors.opportunity_id}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="service_type">Tipo de serviço</Label>
              <Input id="service_type" value={form.data.service_type} onChange={(e) => form.setData('service_type', e.target.value)} placeholder="Site institucional" />
            </div>
            <div className="space-y-2">
              <Label htmlFor="priority">Prioridade *</Label>
              <select id="priority" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="baixa">Baixa</option>
                <option value="normal">Normal</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição geral</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="contracted_scope">Escopo contratado</Label>
              <textarea id="contracted_scope" value={form.data.contracted_scope} onChange={(e) => form.setData('contracted_scope', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="negotiated_value">Valor negociado (BRL)</Label>
              <Input id="negotiated_value" type="number" step="0.01" min="0" value={form.data.negotiated_value} onChange={(e) => form.setData('negotiated_value', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="contracted_at">Data da contratação</Label>
              <Input id="contracted_at" type="date" value={form.data.contracted_at} onChange={(e) => form.setData('contracted_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="desired_deadline">Prazo desejado pelo cliente</Label>
              <Input id="desired_deadline" type="date" value={form.data.desired_deadline} onChange={(e) => form.setData('desired_deadline', e.target.value)} />
              <p className="text-xs text-gray-500 dark:text-gray-400">Prazo desejado, não prazo técnico aprovado.</p>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="internal_notes">Observações internas</Label>
              <textarea id="internal_notes" value={form.data.internal_notes} onChange={(e) => form.setData('internal_notes', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
            </div>
          </CardContent>
        </Card>

        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Briefing (opcional)</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4">
            {([
              ['objectives', 'Objetivo do site ou serviço'],
              ['target_audience', 'Público-alvo'],
              ['business_description', 'Descrição do negócio do cliente'],
              ['products_services', 'Produtos ou serviços oferecidos'],
              ['desired_pages', 'Páginas desejadas'],
              ['desired_features', 'Funcionalidades desejadas'],
              ['references', 'Referências de sites'],
              ['visual_identity', 'Identidade visual existente'],
              ['colors_preferences', 'Cores e preferências visuais'],
              ['available_content', 'Conteúdos disponíveis'],
              ['additional_notes', 'Observações adicionais'],
            ] as const).map(([key, label]) => (
              <div key={key} className="space-y-2">
                <Label htmlFor={key}>{label}</Label>
                <textarea id={key} value={(form.data.briefing as any)[key] ?? ''} onChange={(e) => form.setData(`briefing.${key}` as any, e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
              </div>
            ))}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="domain_info">Domínio existente ou desejado</Label>
                <Input id="domain_info" value={form.data.briefing.domain_info} onChange={(e) => form.setData('briefing.domain_info' as any, e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="hosting_info">Hospedagem existente ou desejada</Label>
                <Input id="hosting_info" value={form.data.briefing.hosting_info} onChange={(e) => form.setData('briefing.hosting_info' as any, e.target.value)} />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Materiais e anexos</CardTitle>
          </CardHeader>
          <CardContent>
            <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.txt,.zip,.mp4" onChange={(e) => form.setData('files', Array.from(e.target.files ?? []))} />
            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Tipos permitidos: pdf, jpg, png, webp, gif, doc, docx, xls, xlsx, txt, zip, mp4. Máximo 10 MB por arquivo. Armazenamento privado.</p>
            {form.errors['files.0'] && <p className="text-sm text-red-600">{form.errors['files.0'] as string}</p>}
          </CardContent>
        </Card>

        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Salvando...' : 'Salvar rascunho'}
        </Button>
      </form>
    </>
  );
}

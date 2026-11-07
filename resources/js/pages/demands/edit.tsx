import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function DemandsEdit({ demand, clients, opportunities }: { demand: any; clients: { id: string; name: string }[]; opportunities: { id: string; title: string; client_id: string }[] }) {
  const b = demand.briefing ?? {};
  const form = useForm({
    title: demand.title ?? '',
    client_id: demand.client_id ?? '',
    opportunity_id: demand.opportunity_id ?? '',
    service_type: demand.service_type ?? '',
    description: demand.description ?? '',
    contracted_scope: demand.contracted_scope ?? '',
    negotiated_value: demand.negotiated_value?.toString() ?? '',
    contracted_at: (demand.contracted_at ?? '').slice(0, 10),
    desired_deadline: (demand.desired_deadline ?? '').slice(0, 10),
    priority: demand.priority ?? 'normal',
    internal_notes: demand.internal_notes ?? '',
    briefing: {
      objectives: b.objectives ?? '',
      target_audience: b.target_audience ?? '',
      business_description: b.business_description ?? '',
      products_services: b.products_services ?? '',
      desired_pages: b.desired_pages ?? '',
      desired_features: b.desired_features ?? '',
      references: b.references ?? '',
      visual_identity: b.visual_identity ?? '',
      colors_preferences: b.colors_preferences ?? '',
      available_content: b.available_content ?? '',
      domain_info: b.domain_info ?? '',
      hosting_info: b.hosting_info ?? '',
      additional_notes: b.additional_notes ?? '',
    },
    files: [] as File[],
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((data) => ({ ...data, files: data.files.length ? data.files : undefined }));
    form.put(`/demands/${demand.id}`, { forceFormData: true });
  }

  const filteredOpps = opportunities.filter((o) => !form.data.client_id || o.client_id === form.data.client_id);

  return (
    <AuthenticatedLayout>
      <Head title="Editar demanda" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900">Editar demanda</h2>
      <form onSubmit={submit} className="space-y-6">
        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Informações gerais</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="title">Título *</Label>
              <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="client_id">Cliente *</Label>
              <select id="client_id" value={form.data.client_id} onChange={(e) => form.setData('client_id', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                <option value="">— Selecione —</option>
                {clients.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="opportunity_id">Oportunidade</Label>
              <select id="opportunity_id" value={form.data.opportunity_id} onChange={(e) => form.setData('opportunity_id', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">— Sem oportunidade —</option>
                {filteredOpps.map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.title}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="service_type">Tipo de serviço</Label>
              <Input id="service_type" value={form.data.service_type} onChange={(e) => form.setData('service_type', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="priority">Prioridade *</Label>
              <select id="priority" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="baixa">Baixa</option>
                <option value="normal">Normal</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição geral</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="contracted_scope">Escopo contratado</Label>
              <textarea id="contracted_scope" value={form.data.contracted_scope} onChange={(e) => form.setData('contracted_scope', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="negotiated_value">Valor negociado (BRL)</Label>
              <Input id="negotiated_value" type="number" step="0.01" value={form.data.negotiated_value} onChange={(e) => form.setData('negotiated_value', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="contracted_at">Data da contratação</Label>
              <Input id="contracted_at" type="date" value={form.data.contracted_at} onChange={(e) => form.setData('contracted_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="desired_deadline">Prazo desejado</Label>
              <Input id="desired_deadline" type="date" value={form.data.desired_deadline} onChange={(e) => form.setData('desired_deadline', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="internal_notes">Observações internas</Label>
              <Input id="internal_notes" value={form.data.internal_notes} onChange={(e) => form.setData('internal_notes', e.target.value)} />
            </div>
          </CardContent>
        </Card>

        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Briefing</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4">
            {([
              ['objectives', 'Objetivo'], ['target_audience', 'Público-alvo'], ['business_description', 'Descrição do negócio'],
              ['products_services', 'Produtos/serviços'], ['desired_pages', 'Páginas'], ['desired_features', 'Funcionalidades'],
              ['references', 'Referências'], ['visual_identity', 'Identidade visual'], ['colors_preferences', 'Cores'],
              ['available_content', 'Conteúdos'], ['additional_notes', 'Observações'],
            ] as const).map(([key, label]) => (
              <div key={key} className="space-y-2">
                <Label htmlFor={key}>{label}</Label>
                <textarea id={key} value={(form.data.briefing as any)[key] ?? ''} onChange={(e) => form.setData(`briefing.${key}` as any, e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={2} />
              </div>
            ))}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="domain_info">Domínio</Label>
                <Input id="domain_info" value={form.data.briefing.domain_info} onChange={(e) => form.setData('briefing.domain_info' as any, e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="hosting_info">Hospedagem</Label>
                <Input id="hosting_info" value={form.data.briefing.hosting_info} onChange={(e) => form.setData('briefing.hosting_info' as any, e.target.value)} />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Anexos adicionais</CardTitle>
          </CardHeader>
          <CardContent>
            <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.txt,.zip,.mp4" onChange={(e) => form.setData('files', Array.from(e.target.files ?? []))} />
            <p className="mt-2 text-xs text-gray-500">Máximo 10 MB por arquivo.</p>
          </CardContent>
        </Card>

        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Salvando...' : 'Salvar alterações'}
        </Button>
      </form>
    </AuthenticatedLayout>
  );
}

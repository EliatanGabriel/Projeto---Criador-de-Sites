import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function DemandsShow({ demand, statusHint, can }: { demand: any; statusHint: string; can: Record<string, boolean> }) {
  const [reason, setReason] = useState('');
  const [notes, setNotes] = useState('');
  const submitForm = useForm({});
  const reviewForm = useForm({});
  const uploadForm = useForm({ file: null as File | null });

  const b = demand.briefing ?? {};
  const histories = [...(demand.histories ?? [])].reverse();

  return (
    <AuthenticatedLayout>
      <Head title={demand.title} />
      <div className="mb-4 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">{demand.title}</h2>
          <p className="mt-1 text-sm text-gray-600">
            {demand.client?.name ?? '—'} • Status: <span className="font-medium">{demand.status}</span> • {statusHint}
          </p>
        </div>
        {can.update && (
          <Link href={`/demands/${demand.id}/edit`}>
            <Button>Editar rascunho</Button>
          </Link>
        )}
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Detalhes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Cliente:</span> {demand.client?.name ?? '—'}</p>
            <p><span className="font-medium">Oportunidade:</span> {demand.opportunity?.title ?? '—'}</p>
            <p><span className="font-medium">Comercial:</span> {demand.commercial_owner?.name ?? '—'}</p>
            <p><span className="font-medium">Técnica:</span> {demand.technical_owner?.name ?? '—'}</p>
            <p><span className="font-medium">Serviço:</span> {demand.service_type ?? '—'}</p>
            <p><span className="font-medium">Valor negociado:</span> {demand.negotiated_value ? Number(demand.negotiated_value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}</p>
            <p><span className="font-medium">Prazo desejado:</span> {demand.desired_deadline ? new Date(demand.desired_deadline).toLocaleDateString('pt-BR') : '—'} <span className="text-xs text-gray-500">(desejado, não técnico)</span></p>
            <p><span className="font-medium">Prioridade:</span> {demand.priority}</p>
            <p><span className="font-medium">Descrição:</span> {demand.description ?? '—'}</p>
            <p><span className="font-medium">Escopo:</span> {demand.contracted_scope ?? '—'}</p>
            <p><span className="font-medium">Enviada em:</span> {demand.submitted_at ? new Date(demand.submitted_at).toLocaleString('pt-BR') : '—'}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Briefing</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Objetivo:</span> {b.objectives ?? '—'}</p>
            <p><span className="font-medium">Público:</span> {b.target_audience ?? '—'}</p>
            <p><span className="font-medium">Negócio:</span> {b.business_description ?? '—'}</p>
            <p><span className="font-medium">Produtos/serviços:</span> {b.products_services ?? '—'}</p>
            <p><span className="font-medium">Páginas:</span> {b.desired_pages ?? '—'}</p>
            <p><span className="font-medium">Funcionalidades:</span> {b.desired_features ?? '—'}</p>
            <p><span className="font-medium">Referências:</span> {b.references ?? '—'}</p>
            <p><span className="font-medium">Identidade:</span> {b.visual_identity ?? '—'}</p>
            <p><span className="font-medium">Cores:</span> {b.colors_preferences ?? '—'}</p>
            <p><span className="font-medium">Conteúdos:</span> {b.available_content ?? '—'}</p>
            <p><span className="font-medium">Domínio:</span> {b.domain_info ?? '—'}</p>
            <p><span className="font-medium">Hospedagem:</span> {b.hosting_info ?? '—'}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Anexos ({demand.attachments?.length ?? 0})</CardTitle>
          </CardHeader>
          <CardContent>
            {(demand.attachments?.length ?? 0) === 0 ? (
              <p className="text-sm text-gray-500">Nenhum anexo enviado.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {demand.attachments.map((a: any) => (
                  <li key={a.id} className="flex items-center justify-between border-b pb-2">
                    <div>
                      <p className="font-medium">{a.original_name}</p>
                      <p className="text-xs text-gray-500">
                        {a.mime} • {a.size ? (a.mime ?? '') : ''} {a.uploader?.name ?? ''} • {new Date(a.created_at).toLocaleDateString('pt-BR')}
                      </p>
                    </div>
                    <a href={`/demands/${demand.id}/attachments/${a.id}/download`} className="text-blue-600 hover:underline">
                      Baixar
                    </a>
                  </li>
                ))}
              </ul>
            )}
            {can.upload && (
              <form
                className="mt-4 flex items-end gap-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  uploadForm.transform((data) => ({ file: data.file }));
                  uploadForm.post(`/demands/${demand.id}/attachments`, { forceFormData: true });
                }}
              >
                <div className="flex-1 space-y-1">
                  <Label htmlFor="file">Enviar anexo (máx 10 MB)</Label>
                  <Input id="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.txt,.zip,.mp4" onChange={(e) => uploadForm.setData('file', e.target.files?.[0] ?? null)} required />
                </div>
                <Button type="submit" disabled={uploadForm.processing}>
                  {uploadForm.processing ? 'Enviando...' : 'Enviar'}
                </Button>
              </form>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Ações</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4 text-sm">
            {can.submit && (
              <Button onClick={() => submitForm.post(`/demands/${demand.id}/submit`)} disabled={submitForm.processing}>
                Enviar para análise
              </Button>
            )}
            {can.startReview && (
              <Button onClick={() => reviewForm.post(`/demands/${demand.id}/start-review`)} disabled={reviewForm.processing}>
                Assumir análise
              </Button>
            )}
            {can.requestInformation && (
              <div className="space-y-2 border-t pt-3">
                <Label htmlFor="reason">Solicitar complementação (motivo obrigatório)</Label>
                <Input id="reason" value={reason} onChange={(e) => setReason(e.target.value)} placeholder="O que falta?" />
                <Button
                  variant="secondary"
                  onClick={() => {
                    reviewForm.transform(() => ({ reason }));
                    reviewForm.post(`/demands/${demand.id}/request-information`);
                  }}
                >
                  Solicitar informações
                </Button>
              </div>
            )}
            {can.approve && (
              <div className="space-y-2 border-t pt-3">
                <Label htmlFor="notes">Observação da aprovação (opcional)</Label>
                <Input id="notes" value={notes} onChange={(e) => setNotes(e.target.value)} />
                <Button
                  onClick={() => {
                    reviewForm.transform(() => ({ notes }));
                    reviewForm.post(`/demands/${demand.id}/approve`);
                  }}
                >
                  Aprovar
                </Button>
              </div>
            )}
            {can.reject && (
              <div className="space-y-2 border-t pt-3">
                <Label htmlFor="rejectReason">Motivo da recusa (obrigatório)</Label>
                <Input id="rejectReason" value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Justificativa" />
                <Button
                  variant="destructive"
                  onClick={() => {
                    reviewForm.transform(() => ({ reason }));
                    reviewForm.post(`/demands/${demand.id}/reject`);
                  }}
                >
                  Recusar
                </Button>
              </div>
            )}
            {can.cancel && (
              <div className="space-y-2 border-t pt-3">
                <Label htmlFor="cancelReason">Motivo do cancelamento (obrigatório)</Label>
                <Input id="cancelReason" value={reason} onChange={(e) => setReason(e.target.value)} />
                <Button
                  variant="outline"
                  onClick={() => {
                    reviewForm.transform(() => ({ reason }));
                    reviewForm.post(`/demands/${demand.id}/cancel`);
                  }}
                >
                  Cancelar demanda
                </Button>
              </div>
            )}
            {!can.submit && !can.startReview && !can.requestInformation && !can.approve && !can.reject && !can.cancel && <p className="text-gray-500">Nenhuma ação disponível no status atual.</p>}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Histórico</CardTitle>
          </CardHeader>
          <CardContent>
            {histories.length === 0 ? (
              <p className="text-sm text-gray-500">Sem histórico.</p>
            ) : (
              <ul className="space-y-3 text-sm">
                {histories.map((h: any) => (
                  <li key={h.id} className="border-b pb-2 last:border-0">
                    <p className="font-medium">
                      {h.event} {h.from_status ? `(${h.from_status} → ${h.to_status})` : ''}
                    </p>
                    {h.description && <p className="text-gray-600">{h.description}</p>}
                    <p className="text-xs text-gray-500">
                      {h.user?.name ?? 'Sistema'} • {new Date(h.created_at).toLocaleString('pt-BR')}
                    </p>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}

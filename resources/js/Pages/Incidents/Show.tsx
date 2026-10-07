import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Blueprint, Button, CaseTimeline, Field, FilePicker, InfoList, MediaGrid, Segmented, StatusStepper, Tag } from '@/Components';
import { FieldError, PageHeader, Section } from '@/Components/Page';
import { date, time } from '@/lib/format';
import type { IncidentDetail } from '@/types';

const KINDS = { 'Respuesta al residente': 'res', 'Nota interna': 'int', 'Acción tomada': 'acc' } as const;
const ROLES = { mantenimiento: 'Mantenimiento', seguridad: 'Seguridad', admin: 'Administración' };
type Props = {
  incident: IncidentDetail; staff: { id: number; name: string; role: string }[]; states: string[];
  can: { manage: boolean; assign: boolean; comment: boolean; internal: boolean; confirm: boolean };
};

/** 95 → "1 h 35 min"; null → "Pendiente" */
const span = (min: number | null) => min == null ? 'Pendiente' : min < 60 ? `${min} min` : min < 2880 ? `${Math.floor(min / 60)} h ${min % 60} min` : `${Math.round(min / 1440)} días`;

export default function Show({ incident: inc, staff, states, can }: Props) {
  const url = `/incidencias/${inc.id}`;
  const keep = { preserveScroll: true };
  const comment = useForm<{ kind: 'res' | 'int' | 'acc'; text: string }>({ kind: 'res', text: '' });
  const detail = useForm({ priority: inc.priority, admin_notes: inc.admin_notes ?? '', resolution: inc.resolution ?? '', recurrence_note: inc.recurrence_note ?? '', recurring: !!inc.recurring });
  const solution = useForm<{ collection: string; files: File[] }>({ collection: 'solution', files: [] });
  const problem = useForm<{ collection: string; files: File[] }>({ collection: 'evidence', files: [] });
  const confirm = useForm({ resolved: true, feedback: '' });
  const [role, setRole] = useState('');
  const kindLabel = (Object.keys(KINDS) as (keyof typeof KINDS)[]).find(k => KINDS[k] === comment.data.kind)!;

  const upload = (f: typeof solution) => f.post(`${url}/evidencias`, { ...keep, forceFormData: true, onSuccess: () => f.reset('files') });
  const answer = (resolved: boolean) => router.post(`${url}/confirmacion`, { resolved, feedback: confirm.data.feedback }, keep);

  return <div>
    <Head title={inc.id} />
    <div style={{ fontSize: 13, marginBottom: 8 }}><Link href="/incidencias">← Incidencias</Link></div>
    <PageHeader kicker={`${inc.id} · ${inc.type}`} title={inc.title} actions={<><Tag status={inc.priority} /><Tag tone="outline">{inc.status}</Tag></>} />
    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(min(320px,100%),1fr))', gap: 32, alignItems: 'start' }}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 28, gridColumn: 'span 2', minWidth: 0 }}>
        <Section title="Detalle">
          <Blueprint style={{ padding: '18px 20px', display: 'flex', flexDirection: 'column', gap: 18 }}>
            {inc.description && <p style={{ margin: 0, lineHeight: 1.6 }}>{inc.description}</p>}
            <InfoList items={[
              { label: 'Ubicación', value: <>{inc.location}{inc.location_type && <span style={{ color: 'var(--hc-muted)' }}> · {inc.location_type}</span>}</> },
              { label: 'Residencial', value: inc.community },
              { label: 'Categoría', value: inc.scope },
              { label: 'Reportó', value: inc.reporter },
              { label: 'Fecha', value: `${date(inc.created_at)} · ${time(inc.created_at)}` },
              { label: 'Reincidencia', value: inc.recurring ? `↻ ${inc.recurrence_note ?? 'Sí'}` : 'No' },
            ]} />
          </Blueprint>
        </Section>

        <Section title="Evidencia del problema">
          <Blueprint style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 14 }}>
            <MediaGrid items={inc.evidence} empty="El reporte no incluye fotos o videos." />
            {can.manage && <FilePicker files={problem.data.files} onChange={f => problem.setData('files', f)} label="Agregar evidencia" />}
            {can.manage && problem.data.files.length > 0 && <div><Button variant="primary" icon="upload" disabled={problem.processing} onClick={() => upload(problem)}>Subir</Button></div>}
          </Blueprint>
        </Section>

        <Section title="Evidencia de la solución">
          <Blueprint style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 14 }}>
            <MediaGrid items={inc.solution} empty="Aún no hay evidencia del cierre. Se sube al resolver el caso." />
            {can.manage && <FilePicker files={solution.data.files} onChange={f => solution.setData('files', f)} label="Agregar evidencia de solución" />}
            <FieldError message={solution.errors.files} />
            {can.manage && solution.data.files.length > 0 && <div><Button variant="primary" icon="upload" disabled={solution.processing} onClick={() => upload(solution)}>Subir</Button></div>}
            {inc.resolution && !can.manage && <p style={{ margin: 0 }}><strong>Resolución:</strong> {inc.resolution}</p>}
          </Blueprint>
        </Section>

        {can.manage && <Section title="Gestión del caso">
          <Blueprint style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 16 }}>
            <Field label="Prioridad"><Segmented options={['Baja', 'Media', 'Alta']} value={detail.data.priority} onChange={v => detail.setData('priority', v as typeof inc.priority)} /></Field>
            {can.internal && <Field label="Notas de administración (internas)" multiline rows={3} value={detail.data.admin_notes} onChange={e => detail.setData('admin_notes', e.target.value)} />}
            <Field label="Resolución aplicada" multiline rows={3} value={detail.data.resolution} onChange={e => detail.setData('resolution', e.target.value)} />
            <Field label="Nota de reincidencia" value={detail.data.recurrence_note} onChange={e => detail.setData('recurrence_note', e.target.value)} placeholder="2 luminarias reportadas en 30 días" />
            <label className="radio"><input type="checkbox" checked={detail.data.recurring} onChange={e => detail.setData('recurring', e.target.checked)} /><span className="dot" />Es un caso recurrente</label>
            <FieldError message={detail.errors.admin_notes || detail.errors.resolution} />
            <div><Button variant="primary" icon="save" disabled={detail.processing} onClick={() => detail.patch(`${url}/detalle`, keep)}>Guardar cambios</Button></div>
          </Blueprint>
        </Section>}

        <Section title="Bitácora">
          <Blueprint style={{ padding: '18px 20px 4px' }}><CaseTimeline entries={inc.timeline ?? []} /></Blueprint>
          {can.comment && <Blueprint style={{ padding: 14, display: 'flex', flexDirection: 'column', gap: 10 }}>
            {can.internal && <Segmented options={Object.keys(KINDS)} value={kindLabel} onChange={v => comment.setData('kind', KINDS[v as keyof typeof KINDS])} />}
            <textarea className="input" rows={3} value={comment.data.text} onChange={e => comment.setData('text', e.target.value)} placeholder={can.internal ? 'Escribe una actualización…' : 'Escribe un mensaje para el equipo…'} />
            <FieldError message={comment.errors.text} />
            <div><Button variant="primary" icon="send" disabled={comment.processing} onClick={() => comment.post(`${url}/comentarios`, { ...keep, onSuccess: () => comment.reset('text') })}>Registrar</Button></div>
          </Blueprint>}
        </Section>
      </div>

      <div style={{ display: 'flex', flexDirection: 'column', gap: 28, minWidth: 0 }}>
        {can.confirm && <Section title="¿Quedó resuelto?">
          <Blueprint style={{ padding: 14, display: 'flex', flexDirection: 'column', gap: 10 }}>
            <span style={{ fontSize: 14 }}>El equipo marcó el caso como resuelto. Confírmalo o avísanos si el problema sigue.</span>
            <textarea className="input" rows={2} value={confirm.data.feedback} onChange={e => confirm.setData('feedback', e.target.value)} placeholder="Comentario (opcional)" />
            <Button variant="primary" icon="check" block onClick={() => answer(true)}>Sí, fue atendido</Button>
            <Button block onClick={() => answer(false)}>Sigue sin resolverse</Button>
          </Blueprint>
        </Section>}

        <Section title="Estado"><Blueprint style={{ padding: 12 }}>
          <StatusStepper steps={states} current={inc.status} onSelect={can.manage ? s => router.patch(`${url}/estado`, { status: s }, keep) : undefined} />
        </Blueprint></Section>

        <Section title="Responsable"><Blueprint style={{ padding: 12, display: 'flex', flexDirection: 'column', gap: 10 }}>
          <span style={{ fontSize: 14 }}>{inc.assignee ?? (inc.assignee_role ? `Rol: ${ROLES[inc.assignee_role as keyof typeof ROLES] ?? inc.assignee_role}` : 'Sin asignar')}</span>
          {can.assign && <>
            <select className="input" value={inc.assignee_id ?? ''} onChange={e => e.target.value && router.patch(`${url}/asignar`, { assignee_id: Number(e.target.value) }, keep)}>
              <option value="">Asignar a una persona…</option>
              {staff.map(s => <option key={s.id} value={s.id}>{s.name} · {ROLES[s.role as keyof typeof ROLES]}</option>)}
            </select>
            <select className="input" value={role} onChange={e => { setRole(e.target.value); e.target.value && router.patch(`${url}/asignar`, { assignee_role: e.target.value }, { ...keep, onSuccess: () => setRole('') }); }}>
              <option value="">…o a un rol</option>
              {Object.entries(ROLES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </>}
        </Blueprint></Section>

        <Section title="Tiempos (SLA)"><Blueprint style={{ padding: 14 }}>
          <InfoList items={[
            { label: 'Primera respuesta', value: span(inc.response_minutes) },
            { label: 'Cierre', value: span(inc.resolution_minutes) },
            { label: 'Confirmación', value: inc.resident_confirmed_at ? `${date(inc.resident_confirmed_at)}` : inc.resident_feedback ? 'Rechazada' : 'Pendiente' },
          ]} />
          {inc.resident_feedback && <p style={{ margin: '12px 0 0', fontSize: 13, color: 'var(--hc-muted)' }}>“{inc.resident_feedback}”</p>}
        </Blueprint></Section>
      </div>
    </div>
  </div>;
}

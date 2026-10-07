import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Blueprint, Button, DataTable, Dialog, Field, Icon, KpiGrid, Meter, Segmented, ServiceAssetCard, Tag } from '@/Components';
import { FieldError, PageHeader, Section } from '@/Components/Page';
import { ago } from '@/lib/format';
import type { LogEntry, ServiceAsset, UpcomingMaintenance } from '@/types';

type Props = {
  assets: ServiceAsset[]; selected: number | null; stats: { total: number; attention: number; avg_health: number | null; water: number };
  upcoming: UpcomingMaintenance[]; log: LogEntry[]; communities: { id: number; name: string }[]; communityId: number;
  statuses: Record<string, string>; can: { operate: boolean; configure: boolean };
};
type Modal = null | 'update' | 'schedule' | 'new' | { complete: UpcomingMaintenance };

const today = () => new Date().toISOString().slice(0, 10);
const shortDate = (iso: string) => new Date(iso + 'T00:00').toLocaleDateString('es-DO', { day: 'numeric', month: 'short' });
const WATER = ['Agua', 'Calidad del agua', 'Almacenamiento'];
const keep = { preserveScroll: true };

export default function Index({ assets, selected: initial, stats, upcoming, log, communities, communityId, statuses, can }: Props) {
  const [selectedId, setSelectedId] = useState(initial);
  const [modal, setModal] = useState<Modal>(null);
  const selected = assets.find(a => a.id === selectedId) ?? assets[0];
  const close = () => setModal(null);

  const update = useForm({ status: '', health: '', availability: '', reading: '', risk: '' });
  const schedule = useForm({ scheduled_for: today(), type: 'preventivo', notes: '' });
  const complete = useForm({ performed_on: today(), health: '', notes: '' });
  const fresh = useForm({ community_id: String(communityId), category: 'Agua', name: '', location: '', capacity: '', reading_label: '', responsible: '', provider: '', routine: '' });

  const openUpdate = () => { if (!selected) return; update.setData({ status: selected.status, health: String(selected.health), availability: String(selected.availability ?? ''), reading: String(selected.reading ?? ''), risk: selected.risk ?? '' }); update.clearErrors(); setModal('update'); };
  // La rutina se captura una por línea y viaja como arreglo.
  const submitNew = () => { fresh.transform(d => ({ ...d, routine: d.routine.split('\n').map(l => l.trim()).filter(Boolean) })); fresh.post('/servicios', { onSuccess: close }); };
  const done = { ...keep, onSuccess: close };
  const breakdownUrl = selected && `/incidencias/crear?${new URLSearchParams({ asunto: `Avería: ${selected.name}`, tipo: 'Mantenimiento', categoria: 'Área común', referencia: [selected.name, selected.location].filter(Boolean).join(' · ') })}`;

  return <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
    <Head title="Servicios generales" />
    <PageHeader kicker="Operación" title="Servicios generales" actions={<>
      {communities.length > 1 && <select className="input" aria-label="Residencial" value={communityId} onChange={e => router.get('/servicios', { residencial: e.target.value })}>{communities.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select>}
      {can.configure && <Button variant="primary" icon="plus" onClick={() => { fresh.clearErrors(); setModal('new'); }}>Nuevo activo</Button>}
    </>} />

    <KpiGrid items={[
      { label: 'Activos críticos', value: String(stats.total), hero: true, span: 2, sub: 'energía, agua y calidad' },
      { label: 'Salud promedio', value: stats.avg_health != null ? stats.avg_health + '%' : '—', sub: 'lectura operativa' },
      { label: 'Agua y calidad', value: String(stats.water), sub: 'pozos, bombas y tanques' },
      { label: 'Requieren atención', value: String(stats.attention), sub: 'seguimiento del equipo' },
    ]} />

    {assets.length === 0 ? <Blueprint style={{ padding: 32, textAlign: 'center', color: 'var(--hc-muted)' }}>Este residencial aún no tiene servicios generales registrados.</Blueprint> : <>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(min(340px,100%),1fr))', gap: 24 }}>
        {assets.map(a => <ServiceAssetCard key={a.id} category={a.category} name={a.name} location={a.location} status={a.status} statusLabel={a.status_label} health={a.health}
          readingLabel={a.reading_label} reading={a.reading} capacity={a.capacity} availability={a.availability} nextMaintenance={a.next_maintenance} overdue={a.overdue}
          responsible={a.responsible} selected={a.id === selected?.id} onClick={() => setSelectedId(a.id)} />)}
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(min(420px,100%),1fr))', gap: 28, alignItems: 'start' }}>
        <Section title="Plan preventivo · próximos mantenimientos">
          <DataTable empty="Sin mantenimientos programados." columns={[
            { key: 'asset', label: 'Servicio', render: (r: UpcomingMaintenance) => <><strong style={{ fontWeight: 500 }}>{r.asset}</strong><br /><span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{r.location}</span></> },
            { key: 'date', label: 'Fecha', render: (r: UpcomingMaintenance) => <>{shortDate(r.date)}{r.overdue && <> <Tag tone="strong">Vencido</Tag></>}</> },
            { key: 'type', label: 'Tipo', render: (r: UpcomingMaintenance) => r.type === 'preventivo' ? 'Preventivo' : 'Correctivo' },
            { key: 'act', label: '', align: 'right', render: (r: UpcomingMaintenance) => can.operate ? <Button variant="ghost" onClick={() => { complete.setData({ performed_on: today(), health: '', notes: '' }); complete.clearErrors(); setModal({ complete: r }); }}>Completar</Button> : null },
          ]} rows={upcoming} />
        </Section>

        {selected && <Section title="Detalle operativo">
          <Blueprint style={{ padding: 20, display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <h3 style={{ margin: 0, fontSize: 22, flex: 1 }}>{selected.name}</h3><Tag status={selected.status_label} />
            </div>
            {selected.risk && <p style={{ margin: 0, fontSize: 14, borderLeft: '2px solid var(--color-accent)', paddingLeft: 12 }}>{selected.risk}</p>}
            <div>
              <div style={{ fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)', marginBottom: 6 }}>Rutina de mantenimiento</div>
              {selected.routine.length === 0 ? <span style={{ fontSize: 13, color: 'var(--hc-muted)' }}>Sin rutina registrada.</span> :
                <ul style={{ margin: 0, padding: 0, listStyle: 'none', display: 'flex', flexDirection: 'column', gap: 6 }}>
                  {selected.routine.map(r => <li key={r} style={{ display: 'flex', gap: 8, fontSize: 14 }}><Icon name="check" size={15} style={{ marginTop: 3, color: 'var(--color-accent-700)' }} />{r}</li>)}
                </ul>}
            </div>
            <div style={{ fontSize: 13, color: 'var(--hc-muted)' }}>Proveedor: <span style={{ color: 'var(--color-text)' }}>{selected.provider ?? '—'}</span>
              {selected.last_maintenance && <> · Último servicio: <span style={{ color: 'var(--color-text)' }}>{shortDate(selected.last_maintenance)}</span></>}</div>
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
              {can.operate && <><Button icon="sliders-horizontal" onClick={openUpdate}>Actualizar lectura</Button><Button icon="calendar-plus" onClick={() => { schedule.clearErrors(); setModal('schedule'); }}>Programar</Button></>}
              <Link href={breakdownUrl!}><Button icon="triangle-alert">Reportar avería</Button></Link>
            </div>
          </Blueprint>
        </Section>}

        <Section title="Agua del residencial">
          <Blueprint style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 14 }}>
            {assets.filter(a => WATER.includes(a.category)).map(a => <div key={a.id}>
              <Meter label={`${a.name} · ${a.reading_label ?? 'Lectura'}`} value={a.reading ?? 0} warn={a.status !== 'operativo'} />
              <span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>{a.location}</span>
            </div>)}
          </Blueprint>
        </Section>

        <Section title="Bitácora técnica">
          <Blueprint style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 14 }}>
            {log.length === 0 && <span style={{ fontSize: 13, color: 'var(--hc-muted)' }}>Aún no hay movimientos.</span>}
            {log.map(l => <div key={l.id} style={{ display: 'flex', flexDirection: 'column', gap: 2, borderLeft: '2px solid var(--color-divider)', paddingLeft: 12 }}>
              <strong style={{ fontWeight: 500, fontSize: 14 }}>{l.action}</strong>
              <span style={{ fontSize: 13.5 }}>{l.detail}</span>
              <span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>{l.who ?? 'Sistema'} · hace {ago(l.at)}</span>
            </div>)}
          </Blueprint>
        </Section>
      </div>
    </>}

    <Dialog open={modal === 'update'} title={`Actualizar · ${selected?.name}`} onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={update.processing} onClick={() => update.patch(`/servicios/${selected!.id}`, done)}>Guardar</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
        <Field label="Estado"><select className="input" value={update.data.status} onChange={e => update.setData('status', e.target.value)}>{Object.entries(statuses).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Field>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 10 }}>
          <Field label="Salud %" type="number" min={0} max={100} value={update.data.health} onChange={e => update.setData('health', e.target.value)} />
          <Field label="Disponib. %" type="number" min={0} max={100} value={update.data.availability} onChange={e => update.setData('availability', e.target.value)} />
          <Field label={selected?.reading_label ? `${selected.reading_label} %` : 'Lectura %'} type="number" min={0} max={100} value={update.data.reading} onChange={e => update.setData('reading', e.target.value)} />
        </div>
        <Field label="Riesgo o pendiente" multiline rows={2} value={update.data.risk} onChange={e => update.setData('risk', e.target.value)} />
        {Object.values(update.errors).map((m, i) => <FieldError key={i} message={m} />)}
      </div>
    </Dialog>

    <Dialog open={modal === 'schedule'} title={`Programar mantenimiento · ${selected?.name}`} onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={schedule.processing} onClick={() => schedule.post(`/servicios/${selected!.id}/mantenimientos`, done)}>Programar</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
        <Field label="Fecha" type="date" min={today()} value={schedule.data.scheduled_for} onChange={e => schedule.setData('scheduled_for', e.target.value)} />
        <Field label="Tipo"><Segmented options={['Preventivo', 'Correctivo']} value={schedule.data.type === 'preventivo' ? 'Preventivo' : 'Correctivo'} onChange={v => schedule.setData('type', v.toLowerCase())} /></Field>
        <Field label="Notas" multiline rows={2} value={schedule.data.notes} onChange={e => schedule.setData('notes', e.target.value)} />
        <FieldError message={schedule.errors.scheduled_for} />
      </div>
    </Dialog>

    <Dialog open={typeof modal === 'object' && modal !== null} title={`Completar servicio · ${typeof modal === 'object' && modal ? modal.complete.asset : ''}`} onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={complete.processing} onClick={() => typeof modal === 'object' && modal && complete.patch(`/servicios/mantenimientos/${modal.complete.id}/completar`, done)}>Confirmar</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
        <Field label="Fecha de realización" type="date" max={today()} value={complete.data.performed_on} onChange={e => complete.setData('performed_on', e.target.value)} />
        <Field label="Salud resultante % (opcional)" type="number" min={0} max={100} value={complete.data.health} onChange={e => complete.setData('health', e.target.value)} />
        <Field label="Notas del servicio" multiline rows={2} value={complete.data.notes} onChange={e => complete.setData('notes', e.target.value)} />
        <FieldError message={complete.errors.performed_on} />
      </div>
    </Dialog>

    <Dialog open={modal === 'new'} title="Nuevo activo" onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={fresh.processing} onClick={submitNew}>Registrar</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
        <Field label="Categoría"><select className="input" value={fresh.data.category} onChange={e => fresh.setData('category', e.target.value)}>{['Energía', 'Agua', 'Calidad del agua', 'Almacenamiento'].map(c => <option key={c}>{c}</option>)}</select></Field>
        <Field label="Nombre" value={fresh.data.name} onChange={e => fresh.setData('name', e.target.value)} required />
        <FieldError message={fresh.errors.name} />
        <Field label="Ubicación" value={fresh.data.location} onChange={e => fresh.setData('location', e.target.value)} />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
          <Field label="Capacidad" value={fresh.data.capacity} onChange={e => fresh.setData('capacity', e.target.value)} placeholder="350 kVA" />
          <Field label="Qué mide la lectura" value={fresh.data.reading_label} onChange={e => fresh.setData('reading_label', e.target.value)} placeholder="Combustible" />
          <Field label="Responsable" value={fresh.data.responsible} onChange={e => fresh.setData('responsible', e.target.value)} />
          <Field label="Proveedor" value={fresh.data.provider} onChange={e => fresh.setData('provider', e.target.value)} />
        </div>
        <Field label="Rutina de mantenimiento (una por línea)" multiline rows={3} value={fresh.data.routine} onChange={e => fresh.setData('routine', e.target.value)} />
      </div>
    </Dialog>
  </div>;
}

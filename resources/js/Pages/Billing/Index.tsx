import { useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button, DataTable, Dialog, Field, KpiGrid, Pager, Segmented, Tag, UnitPicker } from '@/Components';
import type { UnitOption } from '@/Components';
import { FieldError, PageHeader } from '@/Components/Page';
import { date, money, shortMoney } from '@/lib/format';
import type { Charge, FinanceStats, Page, PaymentRow, UnitBalance } from '@/types';

type Props = {
  tab: 'cobros' | 'pagos' | 'unidades'; filters: { status?: string; q?: string; residencial?: string }; communities: { id: number; name: string }[];
  stats: FinanceStats; charges: Page<Charge> | null; payments: Page<PaymentRow> | null; units: Page<UnitBalance> | null; can: { manage: boolean };
};
const TABS = { Cobros: 'cobros', Pagos: 'pagos', Unidades: 'unidades' } as const;
const STATUS = ['Todos', 'Por vencer', 'Vencido', 'Moroso', 'Legal', 'Al día'];
const METHODS = ['Transferencia', 'Efectivo', 'Tarjeta', 'Cheque'];
const today = () => new Date().toISOString().slice(0, 10);
const keep = { preserveScroll: true };

type Modal = null | 'charge' | 'payment' | 'generate' | { pay: Charge };

export default function Index({ tab, filters, communities, stats, charges, payments, units, can }: Props) {
  const [modal, setModal] = useState<Modal>(null);
  const [unit, setUnit] = useState<UnitOption | null>(null);
  const [search, setSearch] = useState(filters.q ?? '');
  const close = () => { setModal(null); setUnit(null); };

  const charge = useForm({ unit_id: '', concept: 'Cuota de mantenimiento', amount: '', due_date: today() });
  const payment = useForm({ unit_id: '', amount: '', method: 'Transferencia', reference: '' });
  const generate = useForm({ community_id: String(filters.residencial ?? communities[0]?.id ?? ''), concept: 'Cuota de mantenimiento ' + new Date().toLocaleDateString('es-DO', { month: 'long', year: 'numeric' }), due_date: today() });

  // Filtros y pestaña viven en la URL; el servidor solo calcula la pestaña activa.
  const go = (q: Record<string, string | number | undefined>) => {
    const next = { tab, ...filters, ...q };
    router.get('/cobros', Object.fromEntries(Object.entries(next).filter(([, v]) => v !== undefined && v !== '' && v !== 'Todos')) as Record<string, string>, { preserveState: true, replace: true });
  };
  useEffect(() => {
    if (search === (filters.q ?? '')) return;
    const t = setTimeout(() => go({ q: search, page: undefined }), 300);
    return () => clearTimeout(t);
  }, [search]);

  const tabLabel = (Object.keys(TABS) as (keyof typeof TABS)[]).find(k => TABS[k] === tab)!;
  const paying = typeof modal === 'object' && modal ? modal.pay : null;
  const pay = useForm({ amount: '', method: 'Transferencia', reference: '' });
  const done = { ...keep, onSuccess: close };

  return <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
    <Head title="Cobros" />
    <PageHeader kicker="Finanzas" title="Cobros" actions={<>
      {communities.length > 1 && <select className="input" aria-label="Residencial" value={filters.residencial ?? ''} onChange={e => go({ residencial: e.target.value, page: undefined })}><option value="">Todos los residenciales</option>{communities.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select>}
      <a href={'/cobros/exportar'}><Button icon="download">Exportar CSV</Button></a>
      {can.manage && <><Button icon="calendar-plus" onClick={() => { generate.clearErrors(); setModal('generate'); }}>Generar cuotas</Button>
        <Button icon="plus" onClick={() => { charge.clearErrors(); setModal('charge'); }}>Nuevo cargo</Button>
        <Button variant="primary" icon="banknote" onClick={() => { payment.clearErrors(); setModal('payment'); }}>Registrar pago</Button></>}
    </>} />

    <KpiGrid items={[
      { label: 'Cuentas por cobrar', value: shortMoney(stats.receivable), hero: true, span: 2, sub: `${stats.in_debt} unidades con saldo` },
      { label: 'Cobrado este mes', value: shortMoney(stats.collected_month) },
      { label: 'Unidades al día', value: String(stats.on_time) },
      { label: 'En proceso legal', value: String(stats.legal) },
    ]} />

    <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
      <Segmented options={Object.keys(TABS)} value={tabLabel} onChange={v => router.get('/cobros', { tab: TABS[v as keyof typeof TABS], ...(filters.residencial ? { residencial: filters.residencial } : {}) }, { preserveState: false })} />
      {tab === 'cobros' && <Segmented options={STATUS} value={filters.status ?? 'Todos'} onChange={v => go({ status: v, page: undefined })} />}
      <input className="input" placeholder="Buscar por unidad (A01-204)" aria-label="Buscar por unidad" value={search} onChange={e => setSearch(e.target.value)} style={{ maxWidth: 240 }} />
    </div>

    {tab === 'cobros' && charges && <>
      <DataTable empty="Sin cobros con esos filtros." rows={charges.data} columns={[
        { key: 'unit', label: 'Unidad', mono: true },
        { key: 'resident', label: 'Residente' },
        { key: 'concept', label: 'Concepto' },
        { key: 'due_date', label: 'Vence', render: (r: Charge) => date(r.due_date) },
        { key: 'balance', label: 'Saldo', align: 'right', render: (r: Charge) => money(r.balance) },
        { key: 'status', label: 'Estado', render: (r: Charge) => <Tag status={r.status} /> },
        { key: 'act', label: '', align: 'right', render: (r: Charge) => can.manage && r.balance > 0 ? <span style={{ whiteSpace: 'nowrap' }}>
          <Button variant="ghost" onClick={() => { pay.setData({ amount: String(r.balance), method: 'Transferencia', reference: '' }); pay.clearErrors(); setModal({ pay: r }); }}>Registrar pago</Button>
          <Button variant="ghost" title={r.status === 'Legal' ? 'Retirar de proceso legal' : 'Enviar a proceso legal'} onClick={() => router.patch(`/cobros/${r.id}/legal`, { legal: r.status !== 'Legal' }, keep)}>{r.status === 'Legal' ? 'Quitar legal' : 'Legal'}</Button></span> : null },
      ]} />
      <Pager meta={charges.meta} noun="cobros" onPage={p => go({ page: p })} />
    </>}

    {tab === 'pagos' && payments && <>
      <DataTable empty="Aún no hay pagos registrados." rows={payments.data} columns={[
        { key: 'reference', label: 'Recibo', mono: true, render: (r: PaymentRow) => r.reference ?? '—' },
        { key: 'date', label: 'Fecha', render: (r: PaymentRow) => date(r.date) },
        { key: 'unit', label: 'Unidad', mono: true },
        { key: 'concept', label: 'Aplicado a', render: (r: PaymentRow) => r.concept ?? 'Abono a la unidad' },
        { key: 'method', label: 'Método' },
        { key: 'amount', label: 'Monto', align: 'right', render: (r: PaymentRow) => money(r.amount) },
      ]} />
      <Pager meta={payments.meta} noun="pagos" onPage={p => go({ page: p })} />
    </>}

    {tab === 'unidades' && units && <>
      <DataTable empty="Sin unidades." onRowClick={(r: UnitBalance) => router.visit('/unidades/' + r.id)} rows={units.data} columns={[
        { key: 'code', label: 'Unidad', mono: true },
        { key: 'building', label: 'Edificio' },
        { key: 'owner', label: 'Propietario', render: (r: UnitBalance) => r.owner ?? '—' },
        { key: 'maintenance_fee', label: 'Cuota', align: 'right', render: (r: UnitBalance) => money(r.maintenance_fee) },
        { key: 'balance', label: 'Balance', align: 'right', render: (r: UnitBalance) => money(r.balance) },
        { key: 'status', label: 'Estado', render: (r: UnitBalance) => <Tag status={r.status} /> },
      ]} />
      <Pager meta={units.meta} noun="unidades" onPage={p => go({ page: p })} />
    </>}

    <Dialog open={!!paying} title={`Registrar pago · ${paying?.unit}`} onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={pay.processing} onClick={() => pay.post(`/cobros/${paying!.id}/pagos`, done)}>Confirmar {money(Number(pay.data.amount) || 0)}</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        <span style={{ fontSize: 14, color: 'var(--hc-muted)' }}>{paying?.concept} · saldo {money(paying?.balance ?? 0)}</span>
        <Field label="Monto (RD$)" type="number" min={0} step="0.01" value={pay.data.amount} onChange={e => pay.setData('amount', e.target.value)} />
        <FieldError message={pay.errors.amount} />
        <Field label="Método"><Segmented options={METHODS} value={pay.data.method} onChange={v => pay.setData('method', v)} /></Field>
        <Field label="Referencia (opcional)" value={pay.data.reference} onChange={e => pay.setData('reference', e.target.value)} placeholder="TR-3001" />
      </div>
    </Dialog>

    <Dialog open={modal === 'payment'} title="Registrar pago" onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={payment.processing || !unit} onClick={() => { payment.transform(d => ({ ...d, unit_id: String(unit!.id) })); payment.post('/cobros/pagos', done); }}>Confirmar {money(Number(payment.data.amount) || 0)}</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        <Field label="Apartamento"><UnitPicker value={unit} onChange={setUnit} /></Field>
        {unit && <span style={{ fontSize: 13, color: 'var(--hc-muted)' }}>Saldo pendiente: {money(unit.balance ?? 0)}. El pago cubre primero los cobros más antiguos.</span>}
        <Field label="Monto (RD$)" type="number" min={0} step="0.01" value={payment.data.amount} onChange={e => payment.setData('amount', e.target.value)} />
        <FieldError message={payment.errors.amount || payment.errors.unit_id} />
        <Field label="Método"><Segmented options={METHODS} value={payment.data.method} onChange={v => payment.setData('method', v)} /></Field>
        <Field label="Referencia (opcional)" value={payment.data.reference} onChange={e => payment.setData('reference', e.target.value)} />
      </div>
    </Dialog>

    <Dialog open={modal === 'charge'} title="Nuevo cargo" onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={charge.processing || !unit} onClick={() => { charge.transform(d => ({ ...d, unit_id: String(unit!.id) })); charge.post('/cobros', done); }}>Crear cargo</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        <Field label="Apartamento"><UnitPicker value={unit} onChange={setUnit} /></Field>
        <Field label="Concepto" value={charge.data.concept} onChange={e => charge.setData('concept', e.target.value)} />
        <FieldError message={charge.errors.concept} />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
          <Field label="Monto (RD$)" type="number" min={0} step="0.01" value={charge.data.amount} onChange={e => charge.setData('amount', e.target.value)} />
          <Field label="Vence" type="date" value={charge.data.due_date} onChange={e => charge.setData('due_date', e.target.value)} />
        </div>
        <FieldError message={charge.errors.amount || charge.errors.unit_id} />
      </div>
    </Dialog>

    <Dialog open={modal === 'generate'} title="Generar cuotas de mantenimiento" onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={generate.processing} onClick={() => generate.post('/cobros/generar', done)}>Generar</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        <span style={{ fontSize: 14, color: 'var(--hc-muted)' }}>Crea un cobro por la cuota de cada apartamento del residencial. Si el concepto ya existe en ese mes, se omite.</span>
        {communities.length > 1 && <Field label="Residencial"><select className="input" value={generate.data.community_id} onChange={e => generate.setData('community_id', e.target.value)}>{communities.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select></Field>}
        <Field label="Concepto" value={generate.data.concept} onChange={e => generate.setData('concept', e.target.value)} />
        <Field label="Vence" type="date" value={generate.data.due_date} onChange={e => generate.setData('due_date', e.target.value)} />
        <FieldError message={generate.errors.concept || generate.errors.community_id} />
      </div>
    </Dialog>
  </div>;
}

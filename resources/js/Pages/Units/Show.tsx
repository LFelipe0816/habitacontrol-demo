import { Head, Link, router } from '@inertiajs/react';
import { Blueprint, DataTable, InfoList, KpiGrid, Tag } from '@/Components';
import { PageHeader, Section } from '@/Components/Page';
import { date, money } from '@/lib/format';
import type { CaseRow, Person, UnitDetail } from '@/types';

type Charge = { id: number; concept: string; amount: number; balance: number; due_date: string; status: string };
type Payment = { id: number; amount: number; method: string; reference: string | null; date: string };
type Request = { id: number; title: string; status: string; response: string | null };
type Lease = { starts_on: string; ends_on: string; monthly_rent: number; deposit: number; authorized_by: string | null };
type Props = { unit: UnitDetail; people: Person[]; lease: Lease | null; charges: Charge[]; payments: Payment[]; complaints: CaseRow[]; reports: CaseRow[]; requests: Request[]; can: { internal: boolean } };

const OCCUPANCY = { propietario: 'Propietario', alquilado: 'Alquilado', vacante: 'Vacante' };
const RELATION = { propietario: 'Propietario', residente: 'Residente', inquilino: 'Inquilino' };

const caseColumns = [
  { key: 'id', label: 'Caso', mono: true },
  { key: 'title', label: 'Asunto' },
  { key: 'priority', label: 'Prioridad', render: (r: CaseRow) => <Tag status={r.priority} /> },
  { key: 'status', label: 'Estado' },
];
const openCase = (r: CaseRow) => router.visit('/incidencias/' + r.id);

export default function Show({ unit, people, lease, charges, payments, complaints, reports, requests, can }: Props) {
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
    <Head title={`Apto ${unit.number}`} />
    <PageHeader
      kicker={can.internal ? <Link href={`/residenciales/${unit.community.id}`} style={{ color: 'inherit' }}>← {unit.community.name}</Link> : unit.community.name}
      title={`Apto ${unit.number}`}
      actions={<Tag tone={unit.occupancy === 'vacante' ? 'outline' : 'quiet'}>{OCCUPANCY[unit.occupancy]}</Tag>} />
    <span style={{ marginTop: -42, fontSize: 14, color: 'var(--hc-muted)' }}>{[unit.building, unit.block, unit.floor ? `Piso ${unit.floor}` : null].filter(Boolean).join(' · ')}</span>

    <KpiGrid items={[
      { label: 'Deuda vigente', value: money(unit.balance), hero: true, span: 2, sub: unit.balance > 0 ? 'Pendiente de pago' : 'Al día' },
      { label: 'Cuota mensual', value: money(unit.maintenance_fee) },
      { label: 'Parqueo', value: unit.parking ?? '—' },
    ]} />

    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(min(420px,100%),1fr))', gap: 28, alignItems: 'start' }}>
      <Section title="Residentes">
        {people.length === 0 ? <Blueprint style={{ padding: 20, color: 'var(--hc-muted)' }}>Sin personas vinculadas.</Blueprint> :
          <DataTable columns={[
            { key: 'name', label: 'Nombre' },
            { key: 'relation', label: 'Relación', render: (r: Person) => RELATION[r.relation] },
            { key: 'document_id', label: 'Cédula', mono: true, render: (r: Person) => r.document_id ?? 'Pendiente' },
            { key: 'phone', label: 'Teléfono', render: (r: Person) => r.phone ?? '—' },
          ]} rows={people} />}
      </Section>

      <Section title="Ficha">
        <Blueprint style={{ padding: 20, display: 'flex', flexDirection: 'column', gap: 20 }}>
          <InfoList items={[
            { label: 'Entrada', value: unit.move_in_date && date(unit.move_in_date) },
            { label: 'Contacto de emergencia', value: unit.emergency_contact && `${unit.emergency_contact.name} · ${unit.emergency_contact.phone}` },
            { label: 'Vehículos', value: unit.vehicles.map(v => `${v.type} ${v.color} · ${v.plate}`).join(', ') },
            ...(lease ? [
              { label: 'Contrato', value: `${date(lease.starts_on)} – ${date(lease.ends_on)}` },
              { label: 'Renta / depósito', value: `${money(lease.monthly_rent)} / ${money(lease.deposit)}` },
              { label: 'Autorizado por', value: lease.authorized_by },
            ] : []),
          ]} />
          {can.internal && unit.notes && <div style={{ borderTop: '1px solid var(--color-divider)', paddingTop: 14, fontSize: 14 }}>
            <span style={{ display: 'block', fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)', marginBottom: 4 }}>Notas de administración</span>{unit.notes}
          </div>}
        </Blueprint>
      </Section>

      <Section title="Estado de cuenta">
        <DataTable columns={[
          { key: 'concept', label: 'Concepto' },
          { key: 'due_date', label: 'Vence', render: (r: Charge) => date(r.due_date) },
          { key: 'balance', label: 'Saldo', align: 'right', render: (r: Charge) => money(r.balance) },
          { key: 'status', label: 'Estado', render: (r: Charge) => <Tag status={r.status} /> },
        ]} rows={charges} />
      </Section>

      <Section title="Últimos pagos">
        <DataTable columns={[
          { key: 'reference', label: 'Referencia', mono: true, render: (r: Payment) => r.reference ?? '—' },
          { key: 'date', label: 'Fecha', render: (r: Payment) => date(r.date) },
          { key: 'method', label: 'Método' },
          { key: 'amount', label: 'Monto', align: 'right', render: (r: Payment) => money(r.amount) },
        ]} rows={payments} />
      </Section>

      <Section title="Quejas contra la unidad"><DataTable onRowClick={openCase} columns={caseColumns} rows={complaints} /></Section>
      <Section title="Reportes realizados"><DataTable onRowClick={openCase} columns={caseColumns} rows={reports} /></Section>
      <Section title="Solicitudes">
        <DataTable columns={[
          { key: 'title', label: 'Solicitud', render: (r: Request) => <>{r.title}<br /><span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{r.response ?? 'Pendiente de respuesta'}</span></> },
          { key: 'status', label: 'Estado' },
        ]} rows={requests} />
      </Section>
    </div>
  </div>;
}

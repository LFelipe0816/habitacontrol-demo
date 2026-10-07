import { Head, router } from '@inertiajs/react';
import { BarChart, Blueprint, DataTable, KpiGrid, Tag } from '@/Components';
import { PageHeader, Section } from '@/Components/Page';
import { money, shortMoney } from '@/lib/format';
import type { DashboardSummary, Incident } from '@/types';

export default function Dashboard({ summary: k, openIncidents }: { summary: DashboardSummary; openIncidents: Incident[] }) {
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
    <Head title="Panel" />
    <PageHeader kicker="Resumen" title="Panel" />
    <KpiGrid items={[
      { label: 'Cobrado este mes', value: shortMoney(k.collected_month), sub: `de ${money(k.billed_month)}`, hero: true, span: 2, onClick: () => router.visit('/cobros') },
      { label: 'Tasa de cobro', value: k.collection_rate + '%' },
      { label: 'Unidades morosas', value: String(k.overdue_units), onClick: () => router.visit('/cobros?status=Moroso') },
      { label: 'Incidencias abiertas', value: String(k.open_incidents), onClick: () => router.visit('/incidencias') },
      { label: 'Visitas hoy', value: String(k.visitors_today), onClick: () => router.visit('/seguridad') },
    ]} />
    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(min(420px,100%),1fr))', gap: 28 }}>
      <Section title="Facturado vs cobrado · miles RD$">
        <Blueprint style={{ padding: 20 }}><BarChart data={k.monthly} /></Blueprint>
      </Section>
      <Section title="Incidencias abiertas">
        <DataTable
          onRowClick={r => router.visit('/incidencias/' + r.id)}
          columns={[
            { key: 'id', label: 'Caso', mono: true },
            { key: 'title', label: 'Asunto' },
            { key: 'priority', label: 'Prioridad', render: r => <Tag status={r.priority} /> },
            { key: 'status', label: 'Estado' },
          ]}
          rows={openIncidents}
        />
      </Section>
    </div>
  </div>;
}
